<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\ServerConfig;
use App\Util\PathEncoder;
use Exception;

class FileRepository
{
    public function __construct(
        private readonly ServerConfig $config,
        private readonly PathEncoder $pathEncoder
    ) {
    }

    public function saveFile(array $file, bool $isPublic, string $directory): string
    {
        $tmpFilePath = $file['tmp_name'];
        $originalName = $file['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $extension;

        $baseDir = $isPublic ? $this->config->getPublicDir() : $this->config->getPrivateDir();
        $relativePath = $directory . '/' . $newFileName;
        $fullDir = $baseDir . '/' . $directory;
        $destination = $baseDir . '/' . $relativePath;

        $this->ensureDirectoryExists($fullDir);

        if (!move_uploaded_file($tmpFilePath, $destination)) {
            throw new Exception("File upload failed");
        }

        return $this->pathEncoder->encode($relativePath);
    }

    public function getFilePath(string $encodedFilename, ?string $size = null): string
    {
        $decodedPath = $this->pathEncoder->decode($encodedFilename);

        if (empty($size)) {
            return $this->config->getPublicDir() . '/' . $decodedPath;
        }

        $pathParts = explode('/', $decodedPath);
        $directory = $pathParts[0];
        $filename = end($pathParts);

        return $this->config->getPublicDir() . '/' . $directory . '/' . $size . '/' . $filename;
    }

    public function deleteFilePath(string $encodedFilename, ?string $size = null): bool
    {
        $decodedPath = $this->pathEncoder->decode($encodedFilename);

        if (empty($size)) {
            return unlink($this->config->getPublicDir() . '/' . $decodedPath);
        }

        $pathParts = explode('/', $decodedPath);
        $directory = $pathParts[0];
        $filename = end($pathParts);

        return unlink($this->config->getPublicDir() . '/' . $directory . '/' . $size . '/' . $filename);
    }

    public function getDecodedPath(string $encodedFilename): string
    {
        return $this->pathEncoder->decode($encodedFilename);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new Exception("Failed to create directory: {$directory}");
            }
        }
    }
}