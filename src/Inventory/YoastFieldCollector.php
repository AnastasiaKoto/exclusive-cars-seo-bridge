<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Inventory;

use ExclusiveCars\SeoBridge\Contract\YoastFields;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;

final class YoastFieldCollector
{
    public function __construct(private FieldPolicy $fieldPolicy)
    {
    }

    public function collect(array $postTypes, array $taxonomies): array
    {
        if (!defined('WPSEO_VERSION') || !function_exists('YoastSEO')) {
            return [];
        }

        $result = [];
        $scopes = [
            'post' => $postTypes,
            'term' => $taxonomies,
        ];

        $fields = [
            YoastFields::TITLE => 'SEO Title',
            YoastFields::DESCRIPTION => 'Meta Description',
        ];

        foreach ($scopes as $objectKind => $types) {
            foreach ($types as $sourceType) {
                foreach ($fields as $field => $label) {
                    $result[] = [
                        'object_kind' => $objectKind,
                        'source_type' => $sourceType['name'],
                        'name' => 'yoast.' . $field,
                        'label' => $label,
                        'source' => 'yoast',
                        'type' => 'string',
                        'nullable' => true,
                        'access' => $objectKind === 'post' ? $this->fieldPolicy->post($sourceType['name'], 'yoast', $field) : $this->fieldPolicy->term($sourceType['name'], 'yoast', $field),
                    ];
                }
            }
        }

        return $result;
    }
}
