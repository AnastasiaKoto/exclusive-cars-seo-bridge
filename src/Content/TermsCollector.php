<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;

use ExclusiveCars\SeoBridge\Inventory\TaxonomyCollector;
use RuntimeException;

final class TermsCollector
{
    public function __construct(
        private TaxonomyCollector $taxonomyCollector,
        private TermStateCollector $termStateCollector
    ) {
    }

    public function collect(int $page, int $perPage): array
    {
        $taxonomy_names = $this->taxonomyCollector->names();
        $terms = get_terms( [
            'taxonomy' => $taxonomy_names,
            'hide_empty' => false,
            'number'     => $perPage,
            'offset'     => ($page - 1) * $perPage,
            'orderby'    => 'term_id',
            'order'      => 'ASC',
        ]);
        if (is_wp_error($terms)) {
            throw new RuntimeException($terms->get_error_message());
        }

        $items = [];
        foreach ($terms as $term) {
            $items[] = $this->termStateCollector->collect($term);
        }

        $counts = wp_count_terms([
            'taxonomy' => $taxonomy_names,
            'hide_empty' => false
        ]);
        if (is_wp_error($counts)) {
            throw new RuntimeException($counts->get_error_message());
        }


        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil($counts / $perPage),
                'total_items' => (int) $counts,
            ],
        ];
    }
}
