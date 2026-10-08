<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;

use ExclusiveCars\SeoBridge\Contract\YoastFields;

final class YoastValueCollector
{
    public function collect(int $postId): array
    {
        if (!defined('WPSEO_VERSION') || !function_exists('YoastSEO')) {
            return [];
        }

        $rendered = ['title' => null, 'description' => null];
        $presentation = YoastSEO()->meta->for_post($postId);
        if ($presentation !== false) {
            $rendered['title'] = $presentation->title;
            $rendered['description'] = $presentation->description;
        }

        $result = [];
        foreach (YoastFields::META_KEYS as $field => $metaKey) {
            $exists = metadata_exists('post', $postId, $metaKey);
            $raw = $exists ? get_post_meta($postId, $metaKey, true) : null;
            $result[$field] = [
                'raw' => is_string($raw) ? $raw : null,
                'rendered' => $rendered[$field],
                'exists' => $exists,
            ];
        }
        return $result;
    }
}
