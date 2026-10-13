<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Security;

use ExclusiveCars\SeoBridge\Contract\FieldGroups;
use ExclusiveCars\SeoBridge\Contract\PostFields;
use ExclusiveCars\SeoBridge\Contract\YoastFields;
use ExclusiveCars\SeoBridge\Contract\TermFields;
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

    public function post(string $postType, string $group, string $field, ?int $postId = null): array
    {
        return match ($group) {
            FieldGroups::CORE => $this->core($field),
            FieldGroups::ACF => $this->acfForPost($postType, $field, $postId),
            FieldGroups::YOAST => $this->yoast($field),
            default => self::DENIED,
        };
    }

    public function term(string $taxonomy, string $group, string $field): array
    {
        return match ($group) {
            FieldGroups::CORE => $this->coreTerm($field),
            FieldGroups::YOAST => $this->yoast($field),
            default => self::DENIED,
        };
    }

    public function acfForPost(string $postType, string $fieldKey, ?int $postId = null): array
    {
        if (!$this->canWriteAcf($fieldKey)
            || !function_exists('acf_get_field')
            || !function_exists('acf_get_field_group')
            || !function_exists('acf_get_field_group_visibility')) {
            return self::READ_ONLY;
        }

        $field = acf_get_field($fieldKey);
        $group = is_array($field) ? acf_get_field_group($field['parent'] ?? 0) : false;
        if (!is_array($group)) {
            return self::READ_ONLY;
        }

        $screen = ['post_type' => $postType];
        if ($postId !== null) {
            $screen['post_id'] = $postId;
        }

        return acf_get_field_group_visibility($group, $screen)
            ? self::READ_WRITE
            : self::READ_ONLY;
    }

    public function acfForGroup(array $group, string $fieldKey): array
    {
        return $this->canWriteAcf($fieldKey) && !empty($group['active']) && !empty($group['location'])
            ? self::READ_WRITE
            : self::READ_ONLY;
    }

    public function core(string $field): array
    {
        return match ($field) {
            PostFields::TITLE,
            PostFields::CONTENT => self::READ_WRITE,

            default => self::READ_ONLY,
        };
    }

    public function coreTerm(string $field): array
    {
        return match ($field) {
            TermFields::TERM_NAME,
            TermFields::TERM_DESCRIPTION => self::READ_WRITE,

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

    public function canWriteCoreTerm(string $field): bool
    {
        return in_array(
            $field,
            [
                TermFields::TERM_NAME,
                TermFields::TERM_DESCRIPTION,
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
