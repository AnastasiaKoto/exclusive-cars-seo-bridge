<?php

declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Inventory;

use ExclusiveCars\SeoBridge\Security\FieldPolicy;
final class YoastFieldCollector 
{
    public function __construct(
        private FieldPolicy $fieldPolicy
    )
    {
    }

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
                'access' => $this->fieldPolicy->yoast('title')
            ],
            [
                'name' => 'seo.description',
                'label' => 'Meta Description',
                'source' => 'yoast',
                'type' => 'string',
                'access' => $this->fieldPolicy->yoast('description')
            ],
        ];
    }
}