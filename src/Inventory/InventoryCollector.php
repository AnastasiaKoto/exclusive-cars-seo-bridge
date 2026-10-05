<?php
declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Inventory;

final class InventoryCollector {
    public function __construct(
        private PostTypeCollector $postTypes,
        private TaxonomyCollector $taxonomies,
        private AcfFieldCollector $acfFields,
        private YoastFieldCollector $yoastFields
    ) {}

    public function collect(): array
    {
        return [
            'post_types' => $this->postTypes->collect(),
            'taxonomies' => $this->taxonomies->collect(),
            'acf_fields' => $this->acfFields->collect(),
            'yoast_fields' => $this->yoastFields->collect(),
        ];
    }
}