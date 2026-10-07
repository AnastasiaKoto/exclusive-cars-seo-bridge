<?php
declare(strict_types=1);
namespace ExclusiveCars\SeoBridge\Contract;

final class PostStates
{
    public const PUBLISH = 'publish';
    public const DRAFT = 'draft';

    public const ALLOWED = [
        self::PUBLISH,
        self::DRAFT,
    ];

    private function __construct()
    {
    }
}