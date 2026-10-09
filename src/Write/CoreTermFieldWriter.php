<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use DomainException;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use WP_Term;
use ExclusiveCars\SeoBridge\Contract\TermFields;
use InvalidArgumentException;
use RuntimeException;
final class CoreTermFieldWriter
{
    public function __construct(private FieldPolicy $fieldPolicy)
    {}

    public function write(WP_Term $term, array $fields): void
    {
        $this->validate($fields);

        if (array_key_exists(TermFields::TERM_DESCRIPTION, $fields)) {
            $result = wp_update_term(
                (int) $term->term_id,
                $term->taxonomy,
                wp_slash(['description' => $fields[TermFields::TERM_DESCRIPTION]])
            );

            if (is_wp_error($result)) {
                throw new RuntimeException($result->get_error_message());
            }
        }
    }

    public function validate(array $fields): void
    {
        foreach ($fields as $fieldKey => $value) {
            if (!is_string($fieldKey) || $fieldKey === '') {
                throw new InvalidArgumentException(
                    'Core field key must be a non-empty string.'
                );
            }

            if (!is_string($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Value of core field "%s" must be a string.',
                        $fieldKey
                    )
                );
            }

            if(!$this->fieldPolicy->canWriteCoreTerm($fieldKey)) {
                throw new DomainException(
                    sprintf(
                        'Writing core field "%s" is not allowed.',
                        $fieldKey
                    )
                );
            }
        }
    }
}