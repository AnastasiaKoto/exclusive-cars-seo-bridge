<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use ExclusiveCars\SeoBridge\Contract\YoastFields;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use InvalidArgumentException;
use RuntimeException;
use DomainException;
use WP_Term;
use WPSEO_Taxonomy_Meta;

final class YoastTermFieldWriter
{
    public function __construct(private FieldPolicy $fieldPolicy)
    {
    }

    public function write(WP_Term $term, array $fields): void
    {
        $this->validate($fields);
        if ($fields === []) {
            return;
        }
        
        $all = get_option('wpseo_taxonomy_meta', []);

        $termsMeta = is_array($all)
            ? ($all[$term->taxonomy][$term->term_id] ?? [])
            : [];

        if (!is_array($termsMeta)) {
            $termsMeta = [];
        }
        
        foreach ($fields as $field => $value) {
            $termsMeta[YoastFields::TERM_META_KEYS[$field]] = $value;
        }

        WPSEO_Taxonomy_Meta::set_values($term->term_id, $term->taxonomy, $termsMeta);
    }

    public function restore(WP_Term $term, string $field, array $original): void
    {
        $this->write($term, [
            $field => $original['raw'] ?? '',
        ]);
    }

    public function validate(array $fields): void
    {
        if ($fields !== [] && (!defined('WPSEO_VERSION') || !function_exists('YoastSEO') || !class_exists(WPSEO_Taxonomy_Meta::class))) {
            throw new RuntimeException('Yoast SEO is not available.', 424);
        }
        
        foreach ($fields as $key => $value) {
            if (!is_string($key) || $key === '' || !is_string($value)) {
                throw new InvalidArgumentException('Yoast field and value must be strings.');
            }
            if (!$this->fieldPolicy->canWriteYoast($key)) {
                throw new DomainException('Yoast field is not writable: ' . $key);
            }
        }
    }
}
