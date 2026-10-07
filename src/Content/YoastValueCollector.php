<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;
use ExclusiveCars\SeoBridge\Contract\YoastFields;
final class YoastValueCollector
{
    public function collect(int $post_id): array
    {
        if (! defined('WPSEO_VERSION')) {
            return [];
        }
        $renderedValues = $this->getRenderedValues($post_id);
        return [
            'title' => [
                'raw' => $this->getValue($post_id, YoastFields::META_KEYS[YoastFields::TITLE]),
                'rendered' => $renderedValues['title']
            ],
            'description' => [
                'raw' => $this->getValue($post_id, YoastFields::META_KEYS[YoastFields::DESCRIPTION]),
                'rendered' => $renderedValues['description']
            ]
        ];
    }

    private function getValue(int $post_id, string $metaKey): ?string
    {
        if(!function_exists('get_post_meta')) {
            return null;
        }
        if(!metadata_exists('post', $post_id, $metaKey)) {
            return null;
        }

        $value = get_post_meta($post_id, $metaKey, true);

        if(!is_string($value)) {
            return null;
        }

        return $value;
    }

    private function getRenderedValues(int $post_id): ?array
    {
        $presentation = YoastSEO()->meta->for_post($post_id);

        $renderedTitle = null;
        $renderedDescription = null;

        if ($presentation !== false) {
            $renderedTitle = $presentation->title;
            $renderedDescription = $presentation->description;
        }
        
        return [
            'title' => $renderedTitle,
            'description' => $renderedDescription 
        ];
    }
}