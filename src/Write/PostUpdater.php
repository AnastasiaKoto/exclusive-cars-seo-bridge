<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

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
    private const FIELD_GROUPS = ['core', 'acf', 'yoast'];

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
        if ($fields === [] || array_diff(array_keys($fields), self::FIELD_GROUPS)) {
            throw new InvalidArgumentException('Fields must contain supported, non-empty groups.');
        }

        $core = $this->group($fields, 'core');
        $acf = $this->group($fields, 'acf');
        $yoast = $this->group($fields, 'yoast');
        $this->coreFieldWriter->validate($core);
        $this->acfFieldWriter->validate($acf);
        $this->yoastFieldWriter->validate($yoast);

        if ($core === [] && $acf === [] && $yoast === []) {
            throw new InvalidArgumentException('At least one field must be provided.');
        }
        foreach ($acf as $key => $_) {
            if (!isset($before['acf'][$key])) {
                throw new DomainException('ACF field is not available on this post: ' . $key);
            }
        }

        $requested = ['core' => $core, 'acf' => $acf, 'yoast' => $yoast];
        $changed = [];
        foreach ($requested as $group => $values) {
            foreach ($values as $key => $value) {
                $current = $group === 'yoast'
                    ? ($before['yoast'][$key]['raw'] ?? null)
                    : ($group === 'acf' ? ($before['acf'][$key]['value'] ?? null) : $before['core'][$key]);
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
            if (isset($changed['core'])) {
                $this->coreFieldWriter->write($postId, $changed['core']);
            }
            if (isset($changed['acf'])) {
                $this->acfFieldWriter->write($postId, $changed['acf']);
            }
            if (isset($changed['yoast'])) {
                $this->yoastFieldWriter->write($postId, $changed['yoast']);
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
                $actual = $group === 'yoast'
                    ? ($state['yoast'][$key]['raw'] ?? null)
                    : ($group === 'acf' ? ($state['acf'][$key]['value'] ?? null) : ($state['core'][$key] ?? null));
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
                $values[$group][$key] = $group === 'yoast'
                    ? ($before['yoast'][$key]['raw'] ?? null)
                    : ($group === 'acf' ? ($before['acf'][$key]['value'] ?? null) : $before['core'][$key]);
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
