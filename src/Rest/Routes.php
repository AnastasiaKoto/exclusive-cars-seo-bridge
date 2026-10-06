<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Rest;

use WP_REST_Server;
use ExclusiveCars\SeoBridge\Security\Capabilities;
final class Routes
{

    public function __construct(
        private InventoryController $inventoryController,
        private CapabilitiesController $capabilitiesController,
        private PostsController $postsController
    ) {}

    public function register(): void
    {
        register_rest_route(
            Api::NAMESPACE,
            '/inventory',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [
                    $this->inventoryController,
                    'index'
                ],
                'permission_callback' => static function(): bool {
                    return current_user_can(Capabilities::READ);
                },

            ]
        );
        register_rest_route(
            Api::NAMESPACE,
            '/capabilities',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [
                    $this->capabilitiesController,
                    'index'
                ],
                'permission_callback' => static function(): bool {
                    return current_user_can(Capabilities::READ);
                },

            ]
        );
        register_rest_route(
            Api::NAMESPACE,
            '/posts',
            [
                'methods' => WP_REST_Server::READABLE,

                'callback' => [
                    $this->postsController,
                    'index',
                ],

                'permission_callback' => static function (): bool {
                    return current_user_can(Capabilities::READ);
                },

                'args' => [
                    'page' => [
                        'description' => 'Page number.',
                        'required' => false,
                        'default' => 1,
                        'type' => 'integer',
                        'minimum' => 1,
                    ],

                    'per_page' => [
                        'description' => 'Number of posts per page.',
                        'required' => false,
                        'default' => 50,
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 100,
                    ],
                ],
            ]
        );
    }
}