<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;

use ExclusiveCars\SeoBridge\Contract\FieldGroups;
use RuntimeException;

final class PostVersionCalculator
{
    public function calculate(array $state): string
    {
        $acf = [];
        foreach ($state[FieldGroups::ACF] ?? [] as $key => $field) {
            $acf[$key] = [
                'exists' => $field['exists'] ?? false,
                'value' => $field['value'] ?? null,
            ];
        }

        $yoast = [];
        foreach ($state[FieldGroups::YOAST] ?? [] as $key => $field) {
            $yoast[$key] = [
                'exists' => $field['exists'] ?? false,
                'raw' => $field['raw'] ?? null,
            ];
        }

        $canonical = $this->sortKeys([
            'id' => $state['id'] ?? null,
            FieldGroups::CORE => $state[FieldGroups::CORE] ?? [],
            FieldGroups::ACF => $acf,
            FieldGroups::YOAST => $yoast,
        ]);
        $json = wp_json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Failed to encode state as JSON.');
        }

        return 'sha256:' . hash('sha256', $json);
    }

    private function sortKeys(array $values): array
    {
        if (!array_is_list($values)) {
            ksort($values, SORT_STRING);
        }
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = $this->sortKeys($value);
            }
        }
        unset($value);
        return $values;
    }
}
