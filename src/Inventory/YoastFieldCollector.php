<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Inventory;

use ExclusiveCars\SeoBridge\Security\FieldPolicy;

final class YoastFieldCollector
{
    public function __construct(private FieldPolicy $fieldPolicy)
    {
    }

    public function collect(): array
    {
        if (!defined('WPSEO_VERSION') || !function_exists('YoastSEO')) {
            return [];
        }
        return [
            [
                'name' => 'yoast.title',
                'label' => 'SEO Title',
                'source' => 'yoast',
                'type' => 'string',
                'nullable' => true,
                'access' => $this->fieldPolicy->yoast('title'),
            ],
            [
                'name' => 'yoast.description',
                'label' => 'Meta Description',
                'source' => 'yoast',
                'type' => 'string',
                'nullable' => true,
                'access' => $this->fieldPolicy->yoast('description'),
            ],
        ];
    }
}
