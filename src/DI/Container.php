<?php

declare(strict_types=1);

namespace App\DI;

use Closure;
use Exception;

class Container
{
    private static array $services = [];
    private static array $instances = [];

    public function register(string $name, Closure $factory): void
    {
        self::$services[$name] = $factory;
    }

    public function get(string $name)
    {
        if (!isset(self::$services[$name])) {
            throw new Exception("Service '$name' not found in container");
        }

        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = self::$services[$name]($this);
        }

        return self::$instances[$name];
    }

    public function has(string $name): bool
    {
        return isset(self::$services[$name]);
    }
}