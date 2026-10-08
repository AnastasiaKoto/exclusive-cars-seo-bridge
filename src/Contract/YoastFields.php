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

    public const TERM_META_KEYS = [
        self::TITLE => 'wpseo_title',
        self::DESCRIPTION => 'wpseo_desc',
    ];

    private function __construct()
    {
    }
}