<?php

declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Content;

use WP_Query;
use WP_Post;
use ExclusiveCars\SeoBridge\Inventory\PostTypeCollector;
use ExclusiveCars\SeoBridge\Contract\PostFields;
use ExclusiveCars\SeoBridge\Contract\PostStates;
final class PostsCollector
{
    public function __construct(
        private PostTypeCollector $postTypes,
        private ACFValueCollector $acfValueCollector,
        private YoastValueCollector $yoastValueCollector,
        private PostVersionCalculator $postVersionCalculator,
        private PostStatesCollector $postStatesCollector
    )
    {}

    public function collect(int $page, int $per_page): array
    {
        $items = [];
        $query = new WP_Query([
            'post_type' => $this->postTypes->names(),
            'post_status' => PostStates::ALLOWED,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'ID',
            'order' => 'ASC'
        ]);

        foreach($query->posts as $post) {
            $items[] = $this->postStatesCollector->collect($post);
        }

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => (int) $query->max_num_pages,
                'total_items' => (int) $query->found_posts
            ]
        ];
    }
}