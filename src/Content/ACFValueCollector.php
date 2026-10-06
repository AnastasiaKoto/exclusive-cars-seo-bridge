<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;

final class ACFValueCollector
{
    public function collect(int $post_id): array
    {
        if(!function_exists('get_field_objects')) {
            return [];
        }

        $fields = get_field_objects($post_id, false);

        if(!is_array($fields)) {
            return [];
        }

        $result = [];
        foreach($fields as $field) {
            $result[] = [
                'name' => isset($field['name']) ? $field['name'] : null,
                'key' => isset($field['key']) ? $field['key'] : null,
                'value' => isset($field['value']) ? $field['value'] : null
            ];
        }

        return $result;
    }
}