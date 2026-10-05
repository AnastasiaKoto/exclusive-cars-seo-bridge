<?php

declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Activation;

use WP_Role;
use ExclusiveCars\SeoBridge\Security\Capabilities;
final class Activator
{
    public const ROLE = 'exclusive_cars_seo_service';

    public static function activate(): void
    {
        $role = get_role(self::ROLE);

        if (! $role instanceof WP_Role) {
            add_role(
                self::ROLE,
                'Exclusive Cars SEO Service',
                [
                    'read' => true,
                ]
            );

            $role = get_role(self::ROLE);
        }

        if (! $role instanceof WP_Role) {
            return;
        }

        $role->add_cap('read');
        $role->add_cap(Capabilities::READ);
    }
}