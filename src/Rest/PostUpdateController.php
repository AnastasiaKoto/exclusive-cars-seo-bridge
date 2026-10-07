<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Rest;

use ExclusiveCars\SeoBridge\Write\PostUpdater;
use WP_REST_Request;
use WP_REST_Response;
use InvalidArgumentException;

final class PostUpdateController {
    public function __construct(
        private PostUpdater $postUpdater
    ){}

    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $post_id = (int) $request->get_param('post_id');
        $fields = $request->get_param('fields');
        $expectedVersion = $request->get_param('expected_version');

        if (!is_string($expectedVersion) || $expectedVersion === '') {
            throw new InvalidArgumentException(
                'Expected version must be a non-empty string.'
            );
        }

        if (!is_array($fields)) {
            throw new InvalidArgumentException(
                'Fields must be an array.'
            );
        }

        $snapshots = $this->postUpdater->update($post_id, $expectedVersion, $fields);

        return new WP_REST_Response($snapshots, 200);
    }
}