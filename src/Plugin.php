<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge;

use ExclusiveCars\SeoBridge\Inventory\AcfFieldCollector;
use ExclusiveCars\SeoBridge\Inventory\YoastFieldCollector;
use ExclusiveCars\SeoBridge\Inventory\PostTypeCollector;
use ExclusiveCars\SeoBridge\Inventory\TaxonomyCollector;
use ExclusiveCars\SeoBridge\Inventory\InventoryCollector;
use ExclusiveCars\SeoBridge\Inventory\CoreFieldCollector;

use ExclusiveCars\SeoBridge\Rest\InventoryController;
use ExclusiveCars\SeoBridge\Rest\PostUpdateController;
use ExclusiveCars\SeoBridge\Rest\Routes;
use ExclusiveCars\SeoBridge\Rest\PostsController;
use ExclusiveCars\SeoBridge\Rest\CapabilitiesController;

use ExclusiveCars\SeoBridge\Capabilities\CapabilitiesCollector;

use ExclusiveCars\SeoBridge\Content\PostsCollector;
use ExclusiveCars\SeoBridge\Content\PostStatesCollector;
use ExclusiveCars\SeoBridge\Content\ACFValueCollector;
use ExclusiveCars\SeoBridge\Content\YoastValueCollector;
use ExclusiveCars\SeoBridge\Content\PostVersionCalculator;

use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use ExclusiveCars\SeoBridge\Write\AcfFieldWriter;
use ExclusiveCars\SeoBridge\Write\CoreFieldWriter;
use ExclusiveCars\SeoBridge\Write\PostUpdater;
use ExclusiveCars\SeoBridge\Write\YoastFieldWriter;

final class Plugin
{
    public static function boot(): void
    {
        $inventory_controller = self::buildInventoryController();
        $capabilities_controller = self::buildCapabilitiesController();
        $posts_controller = self::buildPostsController();
        $post_update_controller = self::buildPostUpdateController();

        $routes = new Routes($inventory_controller, $capabilities_controller, $posts_controller, $post_update_controller);

        add_action(
            'rest_api_init',
            [$routes, 'register']
        );
    }

    private static function buildInventoryController(): InventoryController
    {
        $policy = new FieldPolicy();
        $post_types = new PostTypeCollector();
        $taxonomies = new TaxonomyCollector();
        
        $core_fields = new CoreFieldCollector($policy);
        $acf_fields = new AcfFieldCollector($policy);
        $yoast_fields = new YoastFieldCollector($policy);

        $collector = new InventoryCollector($post_types, $taxonomies, $core_fields, $acf_fields, $yoast_fields);
        $controller = new InventoryController($collector);

        return $controller;
    }

    private static function buildCapabilitiesController(): CapabilitiesController
    {
        $collector = new CapabilitiesCollector();
        $controller = new CapabilitiesController($collector);

        return $controller;
    }

    private static function buildPostsController(): PostsController
    {
        $post_types = new PostTypeCollector();
        $acf_value_collector = new ACFValueCollector();
        $yoast_value_collector = new YoastValueCollector();
        $version_calculator = new PostVersionCalculator();
        $states = new PostStatesCollector($acf_value_collector, $yoast_value_collector, $version_calculator);
        $collector = new PostsCollector($post_types, $acf_value_collector, $yoast_value_collector, $version_calculator, $states);
        $controller = new PostsController($collector);

        return $controller;
    }

    private static function buildPostUpdateController(): PostUpdateController
    {
        $post_types = new PostTypeCollector();
        $acf_value_collector = new ACFValueCollector();
        $yoast_value_collector = new YoastValueCollector();
        $version_calculator = new PostVersionCalculator();
        $states = new PostStatesCollector($acf_value_collector, $yoast_value_collector, $version_calculator);
        $policy = new FieldPolicy();
        $core_writer = new CoreFieldWriter($policy);
        $acf_writer = new AcfFieldWriter($policy);
        $yoast_writer = new YoastFieldWriter($policy);

        $post_updater = new PostUpdater($post_types, $states, $core_writer, $acf_writer, $yoast_writer);

        return new PostUpdateController($post_updater);
    }
}