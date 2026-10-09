<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use InvalidArgumentException;
use DomainException;
use RuntimeException;
use Throwable;
use ExclusiveCars\SeoBridge\Contract\FieldGroups;
use ExclusiveCars\SeoBridge\Contract\PostFields;
use ExclusiveCars\SeoBridge\Content\PostStatesCollector;
use WP_Post;
final class PostCreator
{
    public function __construct(
        private FieldGroups $fieldGroups,
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

        $core = $this->group($fields, FieldGroups::CORE);
        $acf = $this->group($fields, FieldGroups::ACF);
        $yoast = $this->group($fields, FieldGroups::YOAST);
        $this->coreFieldWriter->validate($core);
        $this->acfFieldWriter->validate($acf);
        $this->yoastFieldWriter->validate($yoast);

        if ($core === []) {
            throw new InvalidArgumentException('Core fields must be provided.');
        }
        foreach ($acf as $key => $_) {
            if (!isset($before[FieldGroups::ACF][$key])) {
                throw new DomainException('ACF field is not available on this post: ' . $key);
            }
        }

        try {
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
                throw new RuntimeException(
                    'Draft creation failed: ' . $postId->get_error_message()
                );
            }

            if (!is_int($postId) || $postId <= 0) {
                throw new RuntimeException('Draft creation failed: WordPress returned no post ID.');
            }

            if (isset($changed[FieldGroups::ACF])) {
                $this->acfFieldWriter->write($postId, $acf);
            }
            if (isset($changed[FieldGroups::YOAST])) {
                $this->yoastFieldWriter->write($postId, $yoast);
            }
            $after = $this->getPostState($postId);

        } catch (Throwable $error) {
            throw new RuntimeException('Failed to create post', 0, $error);
        }

        return [
            'status' => 'created',
            'after' => $after,
            'version' => $after[PostFields::VERSION],
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
}