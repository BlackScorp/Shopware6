<?php
declare(strict_types=1);

namespace Mollie\Behat\Repository;

final class StaticArrayRepository
{
    static private $staticArray = [];

    public static function add(string $key, $value): void
    {
        self::$staticArray[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return self::$staticArray[$key] ?? $default;
    }
}