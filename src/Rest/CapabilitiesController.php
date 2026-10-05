<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Rest;

use ExclusiveCars\SeoBridge\Capabilities\CapabilitiesCollector;
use WP_REST_Request;
use WP_REST_Response;

final class CapabilitiesController {
    public function __construct(
        private CapabilitiesCollector $collector
    ){}

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $capabilities = $this->collector->collect();

        return new WP_REST_Response($capabilities, 200);
    }
}