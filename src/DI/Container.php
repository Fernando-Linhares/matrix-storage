<?php

declare(strict_types=1);

namespace App\DI;

use Closure;
use Exception;

class Container
{
    private array $services = [];
    private array $instances = [];

    public function register(string $name, Closure $factory): void
    {
        $this->services[$name] = $factory;
    }

    public function get(string $name)
    {
        if (!isset($this->services[$name])) {
            throw new Exception("Service '$name' not found in container");
        }

        if (!isset($this->instances[$name])) {
            $this->instances[$name] = $this->services[$name]($this);
        }

        return $this->instances[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }
}