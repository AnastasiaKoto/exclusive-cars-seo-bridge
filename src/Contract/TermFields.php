<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Contract;

final class TermFields
{
    public const ID = 'id';
    public const TERM_NAME = 'name';
    public const TERM_SLUG = 'slug';
    public const TERM_DESCRIPTION = 'description';
    public const PARENT_ID = 'parent';
    public const URL = 'url';
    public const VERSION = 'version';
    public const TAXONOMY = 'taxonomy';


    public const DEFINITIONS = [
        self::ID => 'integer',
        self::TAXONOMY => 'string',
        self::TERM_NAME => 'string',
        self::TERM_SLUG => 'string',
        self::TERM_DESCRIPTION => 'string',
        self::PARENT_ID => 'integer',
        self::URL => 'string',
        self::VERSION => 'string',
    ];
    private function __construct()
    {
    }
}