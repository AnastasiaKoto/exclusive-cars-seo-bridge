<?php
declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Inventory;

final class InventoryCollector {
    public function __construct(
        private PostTypeCollector $postTypes,
        private TaxonomyCollector $taxonomies,
        private CoreFieldCollector $coreFields,
        private AcfFieldCollector $acfFields,
        private YoastFieldCollector $yoastFields
    ) {}

    public function collect(): array
    {
        $postTypes = $this->postTypes->collect();
        $taxonomies = $this->taxonomies->collect();

        return [
            'post_types' => $postTypes,
            'taxonomies' => $taxonomies,
            'core_fields' => $this->coreFields->collect($postTypes, $taxonomies),
            'acf_fields' => $this->acfFields->collect(),
            'yoast_fields' => $this->yoastFields->collect($postTypes, $taxonomies),
        ];
    }
}