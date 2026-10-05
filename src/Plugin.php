<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge;

use ExclusiveCars\SeoBridge\Inventory\AcfFieldCollector;
use ExclusiveCars\SeoBridge\Inventory\YoastFieldCollector;
use ExclusiveCars\SeoBridge\Inventory\PostTypeCollector;
use ExclusiveCars\SeoBridge\Inventory\TaxonomyCollector;
use ExclusiveCars\SeoBridge\Inventory\InventoryCollector;
use ExclusiveCars\SeoBridge\Rest\InventoryController;
use ExclusiveCars\SeoBridge\Rest\Routes;
use ExclusiveCars\SeoBridge\Capabilities\CapabilitiesCollector;
use ExclusiveCars\SeoBridge\Rest\CapabilitiesController;

final class Plugin
{
    public static function boot(): void
    {
        $inventory_controller = self::buildInventoryController();
        $capabilities_controller = self::buildCapabilitiesController();

        $routes = new Routes($inventory_controller, $capabilities_controller);

        add_action(
            'rest_api_init',
            [$routes, 'register']
        );
    }

    private static function buildInventoryController(): InventoryController
    {
        $post_types = new PostTypeCollector();
        $taxonomies = new TaxonomyCollector();
        $acf_fields = new AcfFieldCollector();
        $yoast_fields = new YoastFieldCollector();

        $collector = new InventoryCollector($post_types, $taxonomies, $acf_fields, $yoast_fields);
        $controller = new InventoryController($collector);

        return $controller;
    }

    private static function buildCapabilitiesController(): CapabilitiesController
    {
        $collector = new CapabilitiesCollector();
        $controller = new CapabilitiesController($collector);

        return $controller;
    }
}