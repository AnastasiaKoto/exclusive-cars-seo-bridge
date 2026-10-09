<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;

use ExclusiveCars\SeoBridge\Contract\FieldGroups;
use ExclusiveCars\SeoBridge\Contract\PostFields;
use WP_Post;

final class PostStatesCollector
{
    public function __construct(
        private ACFValueCollector $acfValueCollector,
        private YoastValueCollector $yoastValueCollector,
        private PostVersionCalculator $postVersionCalculator
    ) {
    }

    public function collect(WP_Post $post): array
    {
        $permalink = get_permalink($post);
        $state = [
            PostFields::ID => (int) $post->ID,
            FieldGroups::CORE => [
                PostFields::POST_TYPE => (string) $post->post_type,
                PostFields::STATUS => (string) $post->post_status,
                PostFields::SLUG => (string) $post->post_name,
                PostFields::TITLE => (string) $post->post_title,
                PostFields::CONTENT => (string) $post->post_content,
                PostFields::EXCERPT => (string) $post->post_excerpt,
                PostFields::PARENT_ID => (int) $post->post_parent,
            ],
            FieldGroups::ACF => $this->acfValueCollector->collect((int) $post->ID),
            FieldGroups::YOAST => $this->yoastValueCollector->collect((int) $post->ID),
            PostFields::MODIFIED_AT => get_post_modified_time(DATE_ATOM, false, $post),
            PostFields::URL => $permalink !== false ? $permalink : null,
        ];
        $state[PostFields::VERSION] = $this->postVersionCalculator->calculate($state);
        return $state;
    }
}
