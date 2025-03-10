<?php

declare(strict_types=1);

namespace App\Util;

use Exception;

class PathEncoder
{
    public function encode(string $path): string
    {
        return implode('-', array_map(fn($key) => bin2hex($key), explode('/', $path)));
    }
    
    public function decode(string $hash): string
    {
        if (!preg_match('/[-]/i', $hash)) {
            throw new Exception("Invalid format {$hash}");
        }
        
        return implode('/', array_map(fn($key) => hex2bin($key), explode('-', $hash)));
    }
}