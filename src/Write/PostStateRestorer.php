<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use RuntimeException;

final class PostStateRestorer
{
    public function __construct(
        private CoreFieldWriter $coreWriter,
        private AcfFieldWriter $acfWriter,
        private YoastFieldWriter $yoastWriter
    ) {
    }

    public function restore(int $postId, array $before, array $changed): void
    {
        foreach (array_reverse(array_keys($changed)) as $group) {
            $fields = $changed[$group];
            if ($group === 'core') {
                $values = [];
                foreach ($fields as $key => $_) {
                    $values[$key] = $before['core'][$key];
                }
                $this->coreWriter->write($postId, $values);
            } elseif ($group === 'acf') {
                foreach ($fields as $key => $_) {
                    $original = $before['acf'][$key] ?? null;
                    if ($original === null || !$original['exists']) {
                        if (!function_exists('delete_field') || !delete_field($key, $postId)) {
                            throw new RuntimeException('ACF compensation failed.');
                        }
                    } else {
                        $this->acfWriter->write($postId, [$key => $original['value']]);
                    }
                }
            } elseif ($group === 'yoast') {
                foreach ($fields as $key => $_) {
                    $original = $before['yoast'][$key];
                    $this->yoastWriter->restore($postId, $key, $original);
                }
            }
        }
    }
}
