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
        private PostsController $postsController,
        private PostUpdateController $postUpdateController,
        private TermsController $termsController
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
        register_rest_route(
            Api::NAMESPACE,
            '/posts/(?P<post_id>\d+)',
            [
                'methods' => WP_REST_Server::EDITABLE,

                'callback' => [
                    $this->postUpdateController,
                    'update',
                ],

                'permission_callback' => static function (): bool {
                    return current_user_can(Capabilities::WRITE);
                },

                'args' => [
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'minimum' => 1,
                    ],
                    'expected_version' => [
                        'required' => true,
                        'type' => 'string',
                        'pattern' => '^sha256:[a-f0-9]{64}$',
                    ],
                    'fields' => [
                        'required' => true,
                        'type' => 'object',
                    ],
                ],
            ]
        );
        register_rest_route(
            Api::NAMESPACE,
            '/terms',
            [
                'methods' => WP_REST_Server::READABLE,

                'callback' => [
                    $this->termsController,
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
                        'description' => 'Number of terms per page.',
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