<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use ExclusiveCars\SeoBridge\Contract\FieldGroups;
use WP_Term;

final class TermStateRestorer
{
    public function __construct(
        private CoreTermFieldWriter $coreTermFieldWriter,
        private YoastTermFieldWriter $yoastTermFieldWriter
    )
    {}

    public function restore(WP_Term $term, array $before, array $changed): void
    {
        foreach (array_reverse(array_keys($changed)) as $group) {
            $fields = $changed[$group];
            if ($group === FieldGroups::CORE) {
                $values = [];
                foreach ($fields as $key => $_) {
                    $values[$key] = $before[FieldGroups::CORE][$key];
                }
                $this->coreTermFieldWriter->write($term, $values);
            } elseif ($group === FieldGroups::YOAST) {
                foreach ($fields as $key => $_) {
                    $original = $before[FieldGroups::YOAST][$key];
                    $this->yoastTermFieldWriter->restore($term, $key, $original);
                }
            }
        }
    }
}