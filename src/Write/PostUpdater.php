<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use ExclusiveCars\SeoBridge\Contract\FieldGroups;
use DomainException;
use ExclusiveCars\SeoBridge\Content\PostStatesCollector;
use ExclusiveCars\SeoBridge\Contract\PostFields;
use ExclusiveCars\SeoBridge\Contract\PostStates;
use ExclusiveCars\SeoBridge\Exception\VersionMismatchException;
use ExclusiveCars\SeoBridge\Inventory\PostTypeCollector;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use WP_Post;

final class PostUpdater
{
    public function __construct(
        private PostTypeCollector $postTypeCollector,
        private PostStatesCollector $postStatesCollector,
        private CoreFieldWriter $coreFieldWriter,
        private AcfFieldWriter $acfFieldWriter,
        private YoastFieldWriter $yoastFieldWriter,
        private PostStateRestorer $restorer
    ) {
    }

    public function update(int $postId, string $expectedVersion, array $fields): array
    {
        $before = $this->getPostState($postId);
        if (!hash_equals($before[PostFields::VERSION], $expectedVersion)) {
            throw new VersionMismatchException('Post version mismatch.');
        }
        if ($fields === [] || array_diff(array_keys($fields), FieldGroups::POST_WRITABLE)) {
            throw new InvalidArgumentException('Fields must contain supported, non-empty groups.');
        }

        $core = $this->group($fields, FieldGroups::CORE);
        $acf = $this->group($fields, FieldGroups::ACF);
        $yoast = $this->group($fields, FieldGroups::YOAST);
        $this->coreFieldWriter->validate($core);
        $this->acfFieldWriter->validate($acf);
        $this->yoastFieldWriter->validate($yoast);

        if ($core === [] && $acf === [] && $yoast === []) {
            throw new InvalidArgumentException('At least one field must be provided.');
        }
        foreach ($acf as $key => $_) {
            if (!isset($before[FieldGroups::ACF][$key])) {
                throw new DomainException('ACF field is not available on this post: ' . $key);
            }
        }

        $requested = [FieldGroups::CORE => $core, FieldGroups::ACF => $acf, FieldGroups::YOAST => $yoast];
        $changed = [];
        foreach ($requested as $group => $values) {
            foreach ($values as $key => $value) {
                $current = $group === FieldGroups::YOAST
                    ? ($before[FieldGroups::YOAST][$key]['raw'] ?? null)
                    : ($group === FieldGroups::ACF ? ($before[FieldGroups::ACF][$key]['value'] ?? null) : $before[FieldGroups::CORE][$key]);
                if ($current !== $value) {
                    $changed[$group][$key] = $value;
                }
            }
        }

        if ($changed === []) {
            return [
                'status' => 'no_change',
                'before' => $before,
                'after' => $before,
                'new_version' => $before[PostFields::VERSION],
                'verification' => 'matched',
                'compensation' => 'not_needed',
            ];
        }

        try {
            if (isset($changed[FieldGroups::CORE])) {
                $this->coreFieldWriter->write($postId, $changed[FieldGroups::CORE]);
            }
            if (isset($changed[FieldGroups::ACF])) {
                $this->acfFieldWriter->write($postId, $changed[FieldGroups::ACF]);
            }
            if (isset($changed[FieldGroups::YOAST])) {
                $this->yoastFieldWriter->write($postId, $changed[FieldGroups::YOAST]);
            }
            $after = $this->getPostState($postId);
            
            if (!$this->matches($after, $requested)) {
                throw new RuntimeException('Read-back verification failed.');
            }
        } catch (Throwable $error) {
            try {
                $this->restorer->restore($postId, $before, $changed);
                $restored = $this->getPostState($postId);
                $compensation = $this->matches($restored, $this->originalValues($before, $changed))
                    ? 'restored' : 'mismatched';
            } catch (Throwable $restoreError) {
                $compensation = 'failed';
            }
            throw new RuntimeException('Apply failed; compensation: ' . $compensation, 0, $error);
        }

        return [
            'status' => 'applied',
            'before' => $before,
            'after' => $after,
            'new_version' => $after[PostFields::VERSION],
            'verification' => 'matched',
            'compensation' => 'not_needed',
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

    private function originalValues(array $before, array $changed): array
    {
        $values = [];
        foreach ($changed as $group => $fields) {
            foreach ($fields as $key => $_) {
                $values[$group][$key] = $group === FieldGroups::YOAST
                    ? ($before[FieldGroups::YOAST][$key]['raw'] ?? null)
                    : ($group === FieldGroups::ACF ? ($before[FieldGroups::ACF][$key]['value'] ?? null) : $before[FieldGroups::CORE][$key]);
            }
        }
        return $values;
    }

    private function getPostState(int $postId): array
    {
        $post = get_post($postId);
        if (!$post instanceof WP_Post) {
            throw new InvalidArgumentException('Post not found.', 404);
        }
        if (!in_array($post->post_type, $this->postTypeCollector->names(), true)) {
            throw new DomainException('Post type is not supported.');
        }
        if (!in_array($post->post_status, PostStates::ALLOWED, true)) {
            throw new DomainException('Post status is not writable.');
        }
        return $this->postStatesCollector->collect($post);
    }
}
