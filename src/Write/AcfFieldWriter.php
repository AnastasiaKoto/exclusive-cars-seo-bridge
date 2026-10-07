<?php
declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Write;

use DomainException;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use InvalidArgumentException;
use RuntimeException;

final class AcfFieldWriter
{
    public function __construct(
        private FieldPolicy $fieldPolicy
    ) {
    }

    public function write(int $postId, array $fields): void
    {
        $this->validate($fields);

        foreach ($fields as $fieldKey => $value) {
            update_field($fieldKey, $value, $postId);
        }
    }

    public function validate(array $fields): void
    {
        if ($fields !== [] && !function_exists('update_field')) {
            throw new RuntimeException('ACF is not available.');
        }
        foreach ($fields as $fieldKey => $value) {
            if (!is_string($fieldKey) || $fieldKey === '') {
                throw new InvalidArgumentException(
                    'ACF field key must be a non-empty string.'
                );
            }

            if (!$this->fieldPolicy->canWriteAcf($fieldKey)) {
                throw new DomainException(
                    sprintf(
                        'Writing ACF field "%s" is not allowed.',
                        $fieldKey
                    )
                );
            }

            if (!is_string($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Value of ACF field "%s" must be a string.',
                        $fieldKey
                    )
                );
            }
        }
    }
}