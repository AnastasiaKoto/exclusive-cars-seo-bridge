<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;

use WP_Term;
use ExclusiveCars\SeoBridge\Contract\TermFields;
use RuntimeException;

final class TermStateCollector
{
    public function __construct(
        private PostVersionCalculator $postVersionCalculator,
        private YoastTermsValueCollector $yoastTermsValueCollector
    ) {
    }

    public function collect(WP_Term $term): array
    {
        $permalink = get_term_link($term);
        if (is_wp_error($permalink)) {
            throw new RuntimeException($permalink->get_error_message());
        }

        $state = [
            TermFields::ID => (int) $term->term_id,
            'core' => [
                TermFields::TAXONOMY => (string) $term->taxonomy,
                TermFields::TERM_NAME => (string) $term->name,
                TermFields::TERM_SLUG => (string) $term->slug,
                TermFields::TERM_DESCRIPTION => (string) $term->description,
                TermFields::PARENT_ID => (int) $term->parent,
            ],
            'acf' => [],
            'yoast' => $this->yoastTermsValueCollector->collect($term),
            TermFields::URL => $permalink !== false ? $permalink : null,
        ];
        $state[TermFields::VERSION] = $this->postVersionCalculator->calculate($state);

        return $state;
    }
}
