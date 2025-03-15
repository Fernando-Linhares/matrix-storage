<?php

declare(strict_types=1);

namespace App\Handler;

use App\Logger\FileLogger;
use Exception;

class ImageHandler implements FileHandlerInterface
{
    private array $supportedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
        'image/webp', 'image/bmp', 'image/tiff', 'image/svg+xml'
    ];

    public function __construct(private readonly FileLogger $logger)
    {
    }

    public function process(string $sourcePath, array $options = []): bool
    {
        // Process the image file
        // This method could be extended based on specific needs
        return true;
    }

    public function supportsType(string $mimeType): bool
    {
        return in_array($mimeType, $this->supportedTypes);
    }

    public function resize(
        string $source, 
        string $size, 
        string $directory, 
        string $filename, 
        bool $isPublic = true
    ): bool {
        if (!preg_match('/^\d+x\d+$/', $size)) {
            throw new Exception('Invalid image size format');
        }

        [$newWidth, $newHeight] = explode('x', $size);
        
        $imageInfo = getimagesize($source);
        if ($imageInfo === false) {
            throw new Exception('Failed to get image information');
        }
        
        [$width, $height, $type] = $imageInfo;
        
        $baseDir = $isPublic ? 
            dirname(dirname($source)) . "/{$directory}/{$size}" : 
            dirname(dirname($source)) . "/{$directory}/{$size}";
        
        if (!is_dir($baseDir)) {
            if (!mkdir($baseDir, 0755, true)) {
                throw new Exception("Failed to create directory: {$baseDir}");
            }
        }
        
        $destination = $baseDir . '/' . basename($filename);
        
        $newImage = imagecreatetruecolor((int)$newWidth, (int)$newHeight);
        if ($newImage === false) {
            throw new Exception('Failed to create new image');
        }
        
        $sourceImage = $this->createImageFromType($source, $type);
        if ($sourceImage === false) {
            throw new Exception('Failed to create source image');
        }
        
        if (!imagecopyresampled(
            $newImage, 
            $sourceImage, 
            0, 0, 0, 0, 
            (int)$newWidth, 
            (int)$newHeight, 
            $width, 
            $height
        )
        ) {
            throw new Exception('Failed to resize image');
        }
        
        $result = $this->saveImage($newImage, $destination, $type);
        
        imagedestroy($newImage);
        imagedestroy($sourceImage);
        
        return $result;
    }

    private function createImageFromType(string $source, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG => imagecreatefrompng($source),
            IMAGETYPE_GIF => imagecreatefromgif($source),
            IMAGETYPE_WEBP => imagecreatefromwebp($source),
            default => false
        };
    }

    private function saveImage($image, string $destination, int $type): bool
    {
        $result = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $destination, 90),
            IMAGETYPE_PNG => imagepng($image, $destination),
            IMAGETYPE_GIF => imagegif($image, $destination),
            IMAGETYPE_WEBP => imagewebp($image, $destination),
            default => false
        };
        
        if (!$result) {
            $this->logger->error("Failed to save image to {$destination}");
        }
        
        return $result;
    }
}