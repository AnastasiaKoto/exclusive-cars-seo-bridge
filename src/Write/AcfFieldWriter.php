<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use DomainException;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use InvalidArgumentException;
use RuntimeException;

final class AcfFieldWriter
{
    public function __construct(private FieldPolicy $fieldPolicy)
    {
    }

    public function write(int $postId, array $fields): void
    {
        $this->validate($fields);
        foreach ($fields as $key => $value) {
            if (update_field($key, $value, $postId) === false
                && get_field($key, $postId, false) !== $value) {
                throw new RuntimeException('ACF update failed: ' . $key);
            }
        }
    }

    public function validate(array $fields): void
    {
        if ($fields !== [] && (!function_exists('update_field') || !function_exists('get_field'))) {
            throw new RuntimeException('ACF is not available.', 424);
        }
        foreach ($fields as $key => $value) {
            if (!is_string($key) || $key === '' || !is_string($value)) {
                throw new InvalidArgumentException('ACF field and value must be strings.');
            }
            if (!$this->fieldPolicy->canWriteAcf($key)) {
                throw new DomainException('ACF field is not writable: ' . $key);
            }
        }
    }
}
