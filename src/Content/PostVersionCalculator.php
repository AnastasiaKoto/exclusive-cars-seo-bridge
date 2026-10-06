<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Content;

use RuntimeException;

final class PostVersionCalculator
{
    public function calculate(array $state): string
    {
        $json = wp_json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Failed to encode state as JSON');
        }

        return 'sha256:' . hash('sha256', $json);
    }
}