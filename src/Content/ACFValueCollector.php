<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;

final class ACFValueCollector
{
    public function collect(int $postId): array
    {
        if (!function_exists('get_field_objects')) {
            return [];
        }

        $fields = get_field_objects($postId, false);
        if (!is_array($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            $key = $field['key'] ?? null;
            if (!is_string($key) || $key === '') {
                continue;
            }

            $result[$key] = [
                'name' => $field['name'] ?? null,
                'value' => $field['value'] ?? null,
                'exists' => true,
            ];
        }

        ksort($result, SORT_STRING);
        return $result;
    }
}
