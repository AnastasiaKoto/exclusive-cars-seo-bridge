<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Rest;

use ExclusiveCars\SeoBridge\Content\PostsCollector;
use WP_REST_Request;
use WP_REST_Response;

final class PostsController {
    public function __construct(
        private PostsCollector $collector
    ){}

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $page = (int) $request->get_param('page');
        $per_page = (int) $request->get_param('per_page');

        $posts = $this->collector->collect($page, $per_page);

        return new WP_REST_Response($posts, 200);
    }
}