<?php

declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Inventory;


final class YoastFieldCollector 
{
    public function collect(): array
    {
        if (! defined('WPSEO_VERSION')) {
            return [];
        }
        return [
            [
                'name' => 'seo.title',
                'label' => 'SEO Title',
                'source' => 'yoast',
                'type' => 'string',
                'readable' => true,
                'writable' => false,
            ],
            [
                'name' => 'seo.description',
                'label' => 'Meta Description',
                'source' => 'yoast',
                'type' => 'string',
                'readable' => true,
                'writable' => false,
            ],
        ];
    }
}