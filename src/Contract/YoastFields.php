<?php

namespace ExclusiveCars\SeoBridge\Contract;

final class YoastFields
{
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';

    public const META_KEYS = [
        self::TITLE => '_yoast_wpseo_title',
        self::DESCRIPTION => '_yoast_wpseo_metadesc',
    ];

    private function __construct()
    {
    }
}