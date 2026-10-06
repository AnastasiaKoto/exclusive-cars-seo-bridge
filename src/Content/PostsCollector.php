<?php

declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Content;

use WP_Query;
use WP_Post;
use ExclusiveCars\SeoBridge\Inventory\PostTypeCollector;
use ExclusiveCars\SeoBridge\Contract\PostFields;
final class PostsCollector
{
    public function __construct(
        private PostTypeCollector $postTypes,
        private ACFValueCollector $acfValueCollector,
        private YoastValueCollector $yoastValueCollector,
        private PostVersionCalculator $postVersionCalculator
    )
    {}

    public function collect(int $page, int $per_page): array
    {
        $items = [];
        $query = new WP_Query([
            'post_type' => $this->postTypes->names(),
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'ID',
            'order' => 'ASC'
        ]);

        foreach($query->posts as $post) {
            $items[] = $this->normilize($post);
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

    private function normilize(WP_Post $post): array
    {
        $permalink = get_permalink($post);
        $acf = $this->acfValueCollector->collect($post->ID);
        $seo = $this->yoastValueCollector->collect($post->ID);
        $postData = [
            PostFields::ID => (int) $post->ID,
            PostFields::POST_TYPE => (string) $post->post_type,
            PostFields::STATUS => (string) $post->post_status,
            PostFields::SLUG => (string) $post->post_name,
            PostFields::TITLE => (string) $post->post_title,
            PostFields::CONTENT => (string) $post->post_content,
            PostFields::EXCERPT => (string) $post->post_excerpt,
            PostFields::PARENT_ID => (int) $post->post_parent,
            'acf' => $acf,
            'seo' => $seo,
        ];
        $postData[PostFields::VERSION] =
            $this->postVersionCalculator->calculate($postData);

        $postData[PostFields::MODIFIED_AT] =
            get_post_modified_time(DATE_ATOM, false, $post);

        $postData[PostFields::URL] =
            $permalink !== false ? $permalink : null;

        return $postData;
    }
}