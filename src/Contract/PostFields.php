<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Contract;

final class PostFields
{
    public const ID = 'id';
    public const POST_TYPE = 'post_type';
    public const STATUS = 'status';
    public const SLUG = 'slug';
    public const TITLE = 'title';
    public const CONTENT = 'content';
    public const EXCERPT = 'excerpt';
    public const PARENT_ID = 'parent_id';
    public const MODIFIED_AT = 'modified_at';
    public const URL = 'url';
    public const VERSION = 'version';

    public const DEFINITIONS = [
        self::ID => 'integer',
        self::POST_TYPE => 'string',
        self::STATUS => 'string',
        self::SLUG => 'string',
        self::TITLE => 'string',
        self::CONTENT => 'string',
        self::EXCERPT => 'string',
        self::PARENT_ID => 'integer',
        self::MODIFIED_AT => 'datetime',
        self::URL => 'string',
        self::VERSION => 'string',
    ];

    private function __construct()
    {
    }
}