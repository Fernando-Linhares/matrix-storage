<?php

declare(strict_types=1);

namespace App\Handler;

interface FileHandlerInterface
{
    public function process(string $sourcePath, array $options = []): bool;
    public function supportsType(string $mimeType): bool;
}