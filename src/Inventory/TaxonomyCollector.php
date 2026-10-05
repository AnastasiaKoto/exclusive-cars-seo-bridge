<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Inventory;

use WP_Taxonomy;
final class TaxonomyCollector
{
    public function collect(): array
    {
        $taxonomies = get_taxonomies(
            [
                'public' => true
            ],
            'objects'
        );

        $result = [];

        foreach($taxonomies as $taxonomy) {
            
            if(!$taxonomy instanceof WP_Taxonomy) {
                continue;
            }

            $counts = wp_count_terms([
                'taxonomy' => $taxonomy->name,
                'hide_empty' => false
            ]);

            $result[] = [
                'name' => $taxonomy->name,
                'label' => $taxonomy->labels->name,
                'rest_base' => $taxonomy->rest_base
                    ?: $taxonomy->name,
                'show_in_rest' => (bool) $taxonomy->show_in_rest,
                'hierarchical' => (bool) $taxonomy->hierarchical,
                'object_types' => $taxonomy->object_type,
                'term_count' => is_wp_error($counts)
                    ? 0
                    : (int) $counts,
            ];
        }

        usort(
            $result,
            static fn (array $left, array $right): int =>
                $left['name'] <=> $right['name']
        );

        return $result;
    }
}