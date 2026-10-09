<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use DomainException;
use ExclusiveCars\SeoBridge\Contract\YoastFields;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use InvalidArgumentException;
use RuntimeException;
use WP_Term;
use WPSEO_Taxonomy_Meta;
use WPSEO_Utils;

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
        if (!is_array($all)) {
            throw new RuntimeException('Yoast taxonomy meta is invalid.');
        }

        $taxonomyMeta = $all[$term->taxonomy] ?? [];
        if (!is_array($taxonomyMeta)) {
            throw new RuntimeException('Yoast taxonomy meta is invalid.');
        }

        $termMeta = $taxonomyMeta[$term->term_id] ?? [];
        if (!is_array($termMeta)) {
            throw new RuntimeException('Yoast term meta is invalid.');
        }

        $expected = [];
        foreach ($fields as $field => $value) {
            $key = YoastFields::TERM_META_KEYS[$field];
            if ($value === null) {
                unset($termMeta[$key]);
                $expected[$key] = null;
            } else {
                $clean = WPSEO_Utils::sanitize_text_field($value);
                $termMeta[$key] = $clean;
                $expected[$key] = $clean;
            }
        }

        if ($termMeta === []) {
            unset($taxonomyMeta[$term->term_id]);
        } else {
            $taxonomyMeta[$term->term_id] = $termMeta;
        }
        if ($taxonomyMeta === []) {
            unset($all[$term->taxonomy]);
        } else {
            $all[$term->taxonomy] = $taxonomyMeta;
        }

        $all['wpseo_already_validated'] = true;
        update_option('wpseo_taxonomy_meta', $all);

        $savedAll = get_option('wpseo_taxonomy_meta', []);
        $saved = is_array($savedAll)
            ? ($savedAll[$term->taxonomy][$term->term_id] ?? [])
            : [];
        if (!is_array($saved)) {
            throw new RuntimeException('Yoast term meta read-back failed.');
        }
        foreach ($expected as $key => $value) {
            if ($value === null) {
                if (array_key_exists($key, $saved)) {
                    throw new RuntimeException('Yoast term meta deletion failed: ' . $key);
                }
            } elseif (!array_key_exists($key, $saved) || $saved[$key] !== $value) {
                throw new RuntimeException('Yoast term meta update failed: ' . $key);
            }
        }

        do_action(
            'edited_term',
            (int) $term->term_id,
            (int) $term->term_taxonomy_id,
            $term->taxonomy,
            []
        );
    }

    public function restore(WP_Term $term, string $field, array $original): void
    {
        $this->write($term, [
            $field => ($original['exists'] ?? false) ? ($original['raw'] ?? null) : null,
        ]);
    }

    public function validate(array $fields): void
    {
        if ($fields !== [] && (
            !defined('WPSEO_VERSION')
            || !function_exists('YoastSEO')
            || !class_exists(WPSEO_Taxonomy_Meta::class)
            || !class_exists(WPSEO_Utils::class)
        )) {
            throw new RuntimeException('Yoast SEO is not available.', 424);
        }

        foreach ($fields as $key => $value) {
            if (!is_string($key) || $key === '' || (!is_string($value) && $value !== null)) {
                throw new InvalidArgumentException('Yoast field must be a string and value must be a string or null.');
            }
            if (!$this->fieldPolicy->canWriteYoast($key)) {
                throw new DomainException('Yoast field is not writable: ' . $key);
            }
        }
    }
}
