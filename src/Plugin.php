<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge;

use ExclusiveCars\SeoBridge\Capabilities\CapabilitiesCollector;
use ExclusiveCars\SeoBridge\Content\ACFValueCollector;
use ExclusiveCars\SeoBridge\Content\PostsCollector;
use ExclusiveCars\SeoBridge\Content\PostStatesCollector;
use ExclusiveCars\SeoBridge\Content\PostVersionCalculator;
use ExclusiveCars\SeoBridge\Content\YoastValueCollector;
use ExclusiveCars\SeoBridge\Inventory\AcfFieldCollector;
use ExclusiveCars\SeoBridge\Inventory\CoreFieldCollector;
use ExclusiveCars\SeoBridge\Inventory\InventoryCollector;
use ExclusiveCars\SeoBridge\Inventory\PostTypeCollector;
use ExclusiveCars\SeoBridge\Inventory\TaxonomyCollector;
use ExclusiveCars\SeoBridge\Inventory\YoastFieldCollector;
use ExclusiveCars\SeoBridge\Rest\CapabilitiesController;
use ExclusiveCars\SeoBridge\Rest\InventoryController;
use ExclusiveCars\SeoBridge\Rest\PostsController;
use ExclusiveCars\SeoBridge\Rest\PostUpdateController;
use ExclusiveCars\SeoBridge\Rest\Routes;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use ExclusiveCars\SeoBridge\Write\AcfFieldWriter;
use ExclusiveCars\SeoBridge\Write\CoreFieldWriter;
use ExclusiveCars\SeoBridge\Write\PostStateRestorer;
use ExclusiveCars\SeoBridge\Write\PostUpdater;
use ExclusiveCars\SeoBridge\Write\YoastFieldWriter;

final class Plugin
{
    public static function boot(): void
    {
        $routes = new Routes(
            self::buildInventoryController(),
            new CapabilitiesController(new CapabilitiesCollector()),
            self::buildPostsController(),
            self::buildPostUpdateController()
        );
        add_action('rest_api_init', [$routes, 'register']);
    }

    private static function states(): PostStatesCollector
    {
        return new PostStatesCollector(
            new ACFValueCollector(),
            new YoastValueCollector(),
            new PostVersionCalculator()
        );
    }

    private static function buildInventoryController(): InventoryController
    {
        $policy = new FieldPolicy();
        return new InventoryController(new InventoryCollector(
            new PostTypeCollector(),
            new TaxonomyCollector(),
            new CoreFieldCollector($policy),
            new AcfFieldCollector($policy),
            new YoastFieldCollector($policy)
        ));
    }

    private static function buildPostsController(): PostsController
    {
        return new PostsController(new PostsCollector(
            new PostTypeCollector(),
            self::states()
        ));
    }

    private static function buildPostUpdateController(): PostUpdateController
    {
        $policy = new FieldPolicy();
        $core = new CoreFieldWriter($policy);
        $acf = new AcfFieldWriter($policy);
        $yoast = new YoastFieldWriter($policy);
        return new PostUpdateController(new PostUpdater(
            new PostTypeCollector(),
            self::states(),
            $core,
            $acf,
            $yoast,
            new PostStateRestorer($core, $acf, $yoast)
        ));
    }
}
