<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Security;

use ExclusiveCars\SeoBridge\Contract\PostFields;
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

    public function acf(string $field): array
    {
        return in_array($field, self::ACF_WRITE_ALLOWLIST, true) ? self::READ_WRITE : self::READ_ONLY;
    }

    public function core(string $field): array
    {
        return match ($field) {
            PostFields::TITLE,
            PostFields::CONTENT => self::READ_WRITE,

            default => self::READ_ONLY,
        };
    }

    public function yoast(string $field): array
    {
        return match ($field) {
            'title',
            'description' => self::READ_WRITE,

            'canonical',
            'robots' => self::READ_ONLY,

            'focus_keyword',
            'keywords' => self::DENIED,

            default => self::DENIED,
        };
    }
}