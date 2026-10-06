<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Inventory;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
final class AcfFieldCollector
{
    public function __construct(
        private FieldPolicy $fieldPolicy
    )
    {
    }

    public function collect(): array
    {
        if (
            ! function_exists('acf_get_field_groups')
            || ! function_exists('acf_get_fields')
        ) {
            return [];
        }

        $groups = acf_get_field_groups();
        $result = [];

        foreach ($groups as $group) {
            $fields = acf_get_fields($group);

            if (! is_array($fields)) {
                $fields = [];
            }

            $result[] = [
                'key' => $group['key'] ?? null,
                'title' => $group['title'] ?? null,
                'fields' => $this->normalizeFields($fields),
            ];
        }

        return $result;
    }

    private function normalizeFields(array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            $access = $this->fieldPolicy->acf($field['key'] ?? '');
            $result[] = [
                'key' => $field['key'] ?? null,
                'name' => $field['name'] ?? null,
                'label' => $field['label'] ?? null,
                'type' => $field['type'] ?? null,
                'required' => (bool) ($field['required'] ?? false),
                'access' => $access,
            ];
        }

        return $result;
    }
}