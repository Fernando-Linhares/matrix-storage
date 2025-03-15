<?php

declare(strict_types=1);

namespace App\Validator;

class FileValidator
{
    public function __construct(private readonly array $allowedTypes = [])
    {
    }

    public function isAllowed(array $file): bool
    {
        return in_array($file['type'], $this->allowedTypes);
    }

    public function validateSizes(array $sizes): bool
    {
        $sum = 0;
        
        foreach ($sizes as $size) {
            if (!preg_match('/^\d+x\d+$/', $size)) {
                return false;
            }

            [$width, $height] = explode('x', $size);
            $sum += (int)$width + (int)$height;
            
            $maxSize = $_ENV['STORAGE_SIZE_LIMIT'] ?? 10000;

            if ($sum > $maxSize) {
                return false;
            }
        }
        
        return true;
    }
}