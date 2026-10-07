<?php
declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Write;

use ExclusiveCars\SeoBridge\Contract\PostFields;
use DomainException;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use InvalidArgumentException;
use RuntimeException;

final class CoreFieldWriter
{
    public function __construct(
        private FieldPolicy $fieldPolicy
    ) {
    }

    public function write(int $postId, array $fields): void
    {
        $this->validate($fields);

        $postData = [
            'ID' => $postId,
        ];

        if (array_key_exists(PostFields::TITLE, $fields)) {
            $postData['post_title'] = (string) $fields[PostFields::TITLE];
        }

        if (array_key_exists(PostFields::CONTENT, $fields)) {
            $postData['post_content'] = (string) $fields[PostFields::CONTENT];
        }

        if (count($postData) === 1) {
            return;
        }

        $result = wp_update_post(
            wp_slash($postData),
            true
        );

        if (is_wp_error($result)) {
            throw new RuntimeException(
                $result->get_error_message()
            );
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

            if(!$this->fieldPolicy->canWriteCore($fieldKey)) {
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