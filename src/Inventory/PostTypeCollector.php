<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Inventory;

use WP_Post_Type;
final class PostTypeCollector
{
    public function collect(): array
    {
        $result = [];

        foreach($this->names() as $postTypeName) {
            $postType = get_post_type_object($postTypeName);
            
            if (!$postType instanceof WP_Post_Type) {
                continue;
            }
            if($postType->name === 'attachment') {
                continue;
            }

            $count = wp_count_posts($postType->name);

            $result[] = [
                'name' => $postType->name,
                'label' => $postType->labels->name,
                'rest_base' => $postType->rest_base ?: $postType->name,
                'show_in_rest' => (bool) $postType->show_in_rest,
                'hierarchical' => (bool) $postType->hierarchical,
                'has_archive' => (bool) $postType->has_archive,
                'published_count' => (int) ($count->publish ?? 0)
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
        $names = get_post_types(
            ['public' => true],
            'names'
        );

        unset($names['attachment']);

        sort($names);

        return array_values($names);
    }
}