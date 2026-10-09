<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Contract;

final class FieldGroups
{
    public const CORE = 'core';
    public const ACF = 'acf';
    public const YOAST = 'yoast';

    public const POST_WRITABLE = [self::CORE, self::ACF, self::YOAST];
    public const TERM_WRITABLE = [self::CORE, self::YOAST];

    private function __construct()
    {
    }
}
