<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;

use WP_Term;

final class YoastTermsValueCollector
{
    public function collect(WP_Term $term): array
    {
        if (!defined('WPSEO_VERSION') || !function_exists('YoastSEO')) {
            return [];
        }

        $all = get_option('wpseo_taxonomy_meta', []);
        $saved = is_array($all)
            ? ($all[$term->taxonomy][$term->term_id] ?? [])
            : [];

        if (!is_array($saved)) {
            $saved = [];
        }

        $presentation = YoastSEO()->meta->for_term((int) $term->term_id);

        return [
            'title' => [
                'raw' => $saved['wpseo_title'] ?? null,
                'rendered' => $presentation !== false ? $presentation->title : null,
                'exists' => array_key_exists('wpseo_title', $saved),
            ],
            'description' => [
                'raw' => $saved['wpseo_desc'] ?? null,
                'rendered' => $presentation !== false ? $presentation->description : null,
                'exists' => array_key_exists('wpseo_desc', $saved),
            ],
        ];
    }
}
