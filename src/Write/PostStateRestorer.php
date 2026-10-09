<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use ExclusiveCars\SeoBridge\Contract\FieldGroups;
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
            if ($group === FieldGroups::CORE) {
                $values = [];
                foreach ($fields as $key => $_) {
                    $values[$key] = $before[FieldGroups::CORE][$key];
                }
                $this->coreWriter->write($postId, $values);
            } elseif ($group === FieldGroups::ACF) {
                foreach ($fields as $key => $_) {
                    $original = $before[FieldGroups::ACF][$key] ?? null;
                    if ($original === null || !$original['exists']) {
                        if (!function_exists('delete_field') || !delete_field($key, $postId)) {
                            throw new RuntimeException('ACF compensation failed.');
                        }
                    } else {
                        $this->acfWriter->write($postId, [$key => $original['value']]);
                    }
                }
            } elseif ($group === FieldGroups::YOAST) {
                foreach ($fields as $key => $_) {
                    $original = $before[FieldGroups::YOAST][$key];
                    $this->yoastWriter->restore($postId, $key, $original);
                }
            }
        }
    }
}
