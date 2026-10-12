<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use InvalidArgumentException;
use DomainException;
use PhpParser\Node\Stmt\Catch_;
use RuntimeException;
use Throwable;
use ExclusiveCars\SeoBridge\Contract\FieldGroups;
use ExclusiveCars\SeoBridge\Contract\PostFields;
use ExclusiveCars\SeoBridge\Content\PostStatesCollector;
use ExclusiveCars\SeoBridge\Inventory\PostTypeCollector;
use WP_Post;
final class PostCreator
{
    public function __construct(
        private PostTypeCollector $postTypeCollector,
        private CoreFieldWriter $coreFieldWriter,
        private AcfFieldWriter $acfFieldWriter,
        private YoastFieldWriter $yoastFieldWriter,
        private PostStatesCollector $postStatesCollector
    )
    {}

    public function create(string $post_type, array $fields): array
    {
        if ($fields === [] || array_diff(array_keys($fields), FieldGroups::POST_WRITABLE)) {
            throw new InvalidArgumentException('Fields must contain supported, non-empty groups.');
        }

        $accessPostTypes = $this->postTypeCollector->names();

        if(!in_array($post_type, $accessPostTypes)) {
            throw new InvalidArgumentException('Incorrect post type');
        }

        $core = $this->group($fields, FieldGroups::CORE);
        $acf = $this->group($fields, FieldGroups::ACF);
        $yoast = $this->group($fields, FieldGroups::YOAST);
        $this->coreFieldWriter->validate($core);
        $this->acfFieldWriter->validate($acf);
        $this->yoastFieldWriter->validate($yoast);

        if ($core === [] && $acf === [] && $yoast === []) {
            throw new InvalidArgumentException('Fields must be provided.');
        }

        $title = $core[PostFields::TITLE] ?? null;

        if (!is_string($title) || trim($title) === '') {
            throw new InvalidArgumentException('Post title must be provided.');
        }

        $postId = wp_insert_post(
            wp_slash([
                'post_type'    => $post_type,
                'post_status'  => 'draft',
                'post_title'   => $core[PostFields::TITLE] ?? '',
                'post_content' => $core[PostFields::CONTENT] ?? '',
            ]),
            true // Возвращать WP_Error при ошибке.
        );
        if (is_wp_error($postId)) {
            if ($postId->get_error_code() === 'empty_content') {
                throw new InvalidArgumentException(
                    'WordPress requires non-empty title or content for this post type.'
                );
            }

            throw new RuntimeException(
                'Draft creation failed: ' . $postId->get_error_message()
            );
        }

        if (!is_int($postId) || $postId <= 0) {
            throw new RuntimeException('Draft creation failed: WordPress returned no post ID.');
        }

        try {
            $this->acfFieldWriter->write($postId, $acf);
            $this->yoastFieldWriter->write($postId, $yoast);

            $after = $this->getPostState($postId);
            $compensation = $this->matches($after, $fields);
            if(!$compensation) {
                throw new RuntimeException('Fields not matched.');
            } else {
                $compensation = 'matched';
            }
        } catch (Throwable $error) {
            try {
                $deleted = wp_delete_post($postId, true);
            } catch (Throwable $deleteError) {
                throw new RuntimeException(
                    'Draft creation failed; draft deletion failed.',
                    0,
                    $deleteError
                );
            }

            if (!$deleted instanceof WP_Post) {
                throw new RuntimeException(
                    'Draft creation failed; deleting the draft also failed.',
                    0,
                    $error
                );
            }

            $code = $error instanceof RuntimeException && $error->getCode() === 424
                ? 424
                : 0;

            throw new RuntimeException(
                'Draft creation failed; draft was deleted.',
                $code,
                $error
            );
        }

        return [
            'status' => 'created',
            'after' => $after,
            'version' => $after[PostFields::VERSION],
            'verification' => $compensation
        ];
    }

    private function group(array $fields, string $name): array
    {
        $group = $fields[$name] ?? [];
        if (!is_array($group)) {
            throw new InvalidArgumentException($name . ' fields must be an object.');
        }
        return $group;
    }

    private function getPostState(int $postId): array
    {
        $post = get_post($postId);
        if (!$post instanceof WP_Post) {
            throw new InvalidArgumentException('Post not found.', 404);
        }
        return $this->postStatesCollector->collect($post);
    }

    private function matches(array $state, array $values): bool
    {
        foreach ($values as $group => $fields) {
            foreach ($fields as $key => $value) {
                $actual = $group === FieldGroups::YOAST
                    ? ($state[FieldGroups::YOAST][$key]['raw'] ?? null)
                    : ($group === FieldGroups::ACF ? ($state[FieldGroups::ACF][$key]['value'] ?? null) : ($state[FieldGroups::CORE][$key] ?? null));
                if ($actual !== $value) {
                    return false;
                }
            }
        }
        return true;
    }
}