<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Capabilities;
use ExclusiveCars\SeoBridge\Rest\Api;
final class CapabilitiesCollector
{
    public function collect(): array
    {
        return [
            'bridge' => $this->collectBridge(),
            'environment' => $this->collectEnvironment(),
            'dependencies' => [
                'acf' => $this->collectAcf(),
                'yoast' => $this->collectYoast(),
            ],
        ];
    }

    private function collectBridge(): array
    {
        return [
            'version' => defined(
                'EXCLUSIVE_CARS_SEO_BRIDGE_VERSION'
            )
                ? (string) constant(
                    'EXCLUSIVE_CARS_SEO_BRIDGE_VERSION'
                )
                : null,

            'api_namespace' => Api::NAMESPACE,
        ];
    }

    private function collectEnvironment(): array
    {
        return [
            'wordpress' => [
                'version' => get_bloginfo('version'),
                'multisite' => is_multisite(),
            ],

            'php' => [
                'version' => PHP_VERSION,
            ],

            'environment_type' => function_exists(
                'wp_get_environment_type'
            )
                ? wp_get_environment_type()
                : 'production',
        ];
    }

    private function collectAcf(): array
    {
        $available = function_exists(
            'acf_get_field_groups'
        );

        return [
            'available' => $available,

            'version' => defined('ACF_VERSION')
                ? (string) constant('ACF_VERSION')
                : null,
        ];
    }

    private function collectYoast(): array
    {
        $available = defined('WPSEO_VERSION') && function_exists('YoastSEO');

        return [
            'available' => $available,

            'version' => $available
                ? (string) constant('WPSEO_VERSION')
                : null,
        ];
    }
}