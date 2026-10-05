<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Rest;

use ExclusiveCars\SeoBridge\Inventory\InventoryCollector;
use WP_REST_Request;
use WP_REST_Response;

final class InventoryController {
    public function __construct(
        private InventoryCollector $collector
    ){}

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $inventory = $this->collector->collect();

        return new WP_REST_Response($inventory, 200);
    }
}