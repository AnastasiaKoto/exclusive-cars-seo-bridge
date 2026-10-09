<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge;

use ExclusiveCars\SeoBridge\Capabilities\CapabilitiesCollector;

use ExclusiveCars\SeoBridge\Content\ACFValueCollector;
use ExclusiveCars\SeoBridge\Content\PostsCollector;
use ExclusiveCars\SeoBridge\Content\PostStatesCollector;
use ExclusiveCars\SeoBridge\Content\PostVersionCalculator;
use ExclusiveCars\SeoBridge\Content\YoastValueCollector;
use ExclusiveCars\SeoBridge\Content\TermsCollector;
use ExclusiveCars\SeoBridge\Content\TermStateCollector;
use ExclusiveCars\SeoBridge\Content\YoastTermsValueCollector;

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
use ExclusiveCars\SeoBridge\Rest\TermsController;
use ExclusiveCars\SeoBridge\Rest\TermUpdateController;
use ExclusiveCars\SeoBridge\Rest\Routes;

use ExclusiveCars\SeoBridge\Security\FieldPolicy;

use ExclusiveCars\SeoBridge\Write\AcfFieldWriter;
use ExclusiveCars\SeoBridge\Write\CoreFieldWriter;
use ExclusiveCars\SeoBridge\Write\PostStateRestorer;
use ExclusiveCars\SeoBridge\Write\PostUpdater;
use ExclusiveCars\SeoBridge\Write\YoastFieldWriter;
use ExclusiveCars\SeoBridge\Write\CoreTermFieldWriter;
use ExclusiveCars\SeoBridge\Write\TermStateRestorer;
use ExclusiveCars\SeoBridge\Write\TermUpdater;
use ExclusiveCars\SeoBridge\Write\YoastTermFieldWriter;

final class Plugin
{
    public static function boot(): void
    {
        $routes = new Routes(
            self::buildInventoryController(),
            new CapabilitiesController(new CapabilitiesCollector()),
            self::buildPostsController(),
            self::buildPostUpdateController(),
            self::buildTermsController(),
            self::buildTermUpdateController()
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

    private static function buildTermsController(): TermsController
    {
        $tax_collector = new TaxonomyCollector();
        $version_calculator = new PostVersionCalculator();
        $yoast = new YoastTermsValueCollector();

        $state = new TermStateCollector($version_calculator, $yoast);
        $collector = new TermsCollector($tax_collector, $state);
        $controller = new TermsController($collector);

        return $controller;
    }

    private static function buildTermUpdateController(): TermUpdateController
    {
        $policy = new FieldPolicy();
        $core = new CoreTermFieldWriter($policy);
        $yoast = new YoastTermFieldWriter($policy);
        $state = new TermStateCollector(new PostVersionCalculator(), new YoastTermsValueCollector());

        return new TermUpdateController(new TermUpdater(
            new TaxonomyCollector(),
            $core,
            $yoast,
            new TermStateRestorer($core, $yoast),
            $state
        ));
    }
}
