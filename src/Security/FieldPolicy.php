<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Security;

use ExclusiveCars\SeoBridge\Contract\PostFields;
use ExclusiveCars\SeoBridge\Contract\YoastFields;
final class FieldPolicy
{
    private const READ_WRITE = [
        'readable' => true,
        'writable' => true,
    ];

    private const READ_ONLY = [
        'readable' => true,
        'writable' => false,
    ];

    private const DENIED = [
        'readable' => false,
        'writable' => false,
    ];

    private const ACF_WRITE_ALLOWLIST = [
        // Услуга
        'field_6690faa048983', // service-descr

        // Услуга R
        'field_6a6cc598d9f81', // short-desc

        // Новость
        'field_6a72fa37bf34d', // short-desc

        // Машина
        'field_668f86ae37a5f', // Description
    ];

    private const YOAST_WRITE_ALLOWLIST = [
        YoastFields::TITLE,
        YoastFields::DESCRIPTION,
    ];

    public function acf(string $field): array
    {
        return in_array($field, self::ACF_WRITE_ALLOWLIST, true) ? self::READ_WRITE : self::READ_ONLY;
    }

    public function canWriteAcf(string $fieldKey): bool
    {
        return in_array(
            $fieldKey,
            self::ACF_WRITE_ALLOWLIST,
            true
        );
    }

    public function core(string $field): array
    {
        return match ($field) {
            PostFields::TITLE,
            PostFields::CONTENT => self::READ_WRITE,

            default => self::READ_ONLY,
        };
    }

    public function canWriteCore(string $field): bool
    {
        return in_array(
            $field,
            [
                PostFields::TITLE,
                PostFields::CONTENT,
            ],
            true
        );
    }

    public function yoast(string $field): array
    {
        if ($this->canWriteYoast($field)) {
            return self::READ_WRITE;
        }

        return match ($field) {
            'canonical',
            'robots' => self::READ_ONLY,

            'focus_keyword',
            'keywords' => self::DENIED,

            default => self::DENIED,
        };
    }

    public function canWriteYoast(string $field): bool
    {
        return in_array(
            $field,
            self::YOAST_WRITE_ALLOWLIST,
            true
        );
    }
}