<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Inventory;

use WP_Taxonomy;
final class TaxonomyCollector
{
    public function collect(): array
    {
        $taxonomy_names = self::names();

        $result = [];

        foreach($taxonomy_names as $name) {

            $taxonomy = get_taxonomy($name);
            
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

    public function names(): array
    {
        $names = get_taxonomies(
            ['public' => true],
            'names'
        );

        sort($names);

        return array_values($names);
    }
}