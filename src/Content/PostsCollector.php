<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;

use ExclusiveCars\SeoBridge\Contract\PostStates;
use ExclusiveCars\SeoBridge\Inventory\PostTypeCollector;
use WP_Query;

final class PostsCollector
{
    public function __construct(
        private PostTypeCollector $postTypes,
        private PostStatesCollector $postStatesCollector
    ) {
    }

    public function collect(int $page, int $perPage): array
    {
        $query = new WP_Query([
            'post_type' => $this->postTypes->names(),
            'post_status' => PostStates::ALLOWED,
            'posts_per_page' => $perPage,
            'paged' => $page,
            'orderby' => 'ID',
            'order' => 'ASC',
        ]);
        $items = [];
        foreach ($query->posts as $post) {
            $items[] = $this->postStatesCollector->collect($post);
        }
        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) $query->max_num_pages,
                'total_items' => (int) $query->found_posts,
            ],
        ];
    }
}
