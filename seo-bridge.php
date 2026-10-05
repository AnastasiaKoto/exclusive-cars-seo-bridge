<?php

/**
 * Plugin Name: Exclusive Cars SEO Bridge
 * Description: Controlled REST bridge between WordPress and the Exclusive Cars SEO service.
 * Version: 0.1.0
 * Requires PHP: 8.2
 */

use ExclusiveCars\SeoBridge\Plugin;
use ExclusiveCars\SeoBridge\Activation\Activator;

if (! defined('ABSPATH')) {
    exit;
}

define('EXCLUSIVE_CARS_SEO_BRIDGE_VERSION', '0.1.0');
define('EXCLUSIVE_CARS_SEO_BRIDGE_FILE', __FILE__);
define('EXCLUSIVE_CARS_SEO_BRIDGE_DIR', __DIR__);

$autoloadPath = __DIR__ . '/vendor/autoload.php';

if (! is_readable($autoloadPath)) {
    return;
}

require_once $autoloadPath;
register_activation_hook(
    __FILE__,
    [Activator::class, 'activate']
);

add_action(
    'plugins_loaded',
    static function (): void {
        if (! class_exists(Plugin::class)) {
            return;
        }

        Plugin::boot();
    }
);