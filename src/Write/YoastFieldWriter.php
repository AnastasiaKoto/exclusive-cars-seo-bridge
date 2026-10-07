<?php
declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Write;

use DomainException;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use InvalidArgumentException;
use RuntimeException;
use ExclusiveCars\SeoBridge\Contract\YoastFields;

final class YoastFieldWriter
{
    public function __construct(
        private FieldPolicy $fieldPolicy
    ) {
    }

    public function write(int $postId, array $fields): void
    {
        $this->validate($fields);

        foreach ($fields as $field => $value) {
            $metaKey = YoastFields::META_KEYS[$field];

            update_post_meta(
                $postId,
                $metaKey,
                $value
            );
        }
    }

    public function validate(array $fields): void
    {
        if ($fields !== [] && (!defined('WPSEO_VERSION') || !function_exists('YoastSEO'))) {
            throw new RuntimeException('Yoast SEO is not available.');
        }
        foreach ($fields as $fieldKey => $value) {
            if (!is_string($fieldKey) || $fieldKey === '') {
                throw new InvalidArgumentException(
                    'Yoast field key must be a non-empty string.'
                );
            }

            if (! is_string($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Value of Yoast field "%s" must be a string.',
                        $fieldKey
                    )
                );
            }

            if (!$this->fieldPolicy->canWriteYoast($fieldKey)) {
                throw new DomainException(
                    sprintf(
                        'Writing Yoast field "%s" is not allowed.',
                        $fieldKey
                    )
                );
            }
        }
    }
}