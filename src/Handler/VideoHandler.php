<?php

declare(strict_types=1);

namespace App\Handler;

use App\Logger\FileLogger;

class VideoHandler implements FileHandlerInterface
{
    private FileLogger $logger;
    private array $supportedTypes = [
        'video/mp4', 'video/quicktime', 'video/x-msvideo',
        'video/x-matroska', 'video/webm'
    ];

    public function __construct(FileLogger $logger)
    {
        $this->logger = $logger;
    }

    public function process(string $sourcePath, array $options = []): bool
    {
        // Video processing logic would go here
        // This could include creating thumbnails, transcoding, etc.
        $this->logger->info("Processing video: {$sourcePath}");
        return true;
    }

    public function supportsType(string $mimeType): bool
    {
        return in_array($mimeType, $this->supportedTypes);
    }
}