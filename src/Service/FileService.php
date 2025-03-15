<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\ServerConfig;
use App\Handler\{ImageHandler};
use App\Logger\FileLogger;
use App\Repository\FileRepository;
use App\Validator\FileValidator;
use Exception;

class FileService
{
    public function __construct(
        private readonly FileRepository $repository,
        private readonly FileValidator $validator,
        private readonly ImageHandler $imageHandler,
        private readonly ServerConfig $config,
        private readonly FileLogger $logger
    ) {
    }

    public function uploadFile(array $file, bool $isPublic, string $appName, array $sizes = []): array
    {
        if ($file['error'] !== 0) {
            throw new Exception('File upload error: ' . $file['error']);
        }

        if (!$this->validator->isAllowed($file)) {
            throw new Exception('File type not allowed');
        }

        if (!empty($sizes) && !$this->validator->validateSizes($sizes)) {
            throw new Exception('Invalid size format or size too large');
        }

        $directory = sha1($appName);
        $filename = $this->repository->saveFile($file, $isPublic, $directory);

        if (strpos($file['type'], 'image/') === 0 && !empty($sizes)) {
            $this->processImageResizing($file, $isPublic, $directory, $filename, $sizes);
        }

        $this->logger->info("File {$filename} saved successfully");

        return [
            'filename' => $filename,
            'path' => $directory
        ];
    }

    public function getFile(string $filename, ?string $size = null): ?array
    {
        try {
            $filePath = $this->repository->getFilePath($filename, $size);

            if (!file_exists($filePath)) {
                return null;
            }

            return [
                'content' => file_get_contents($filePath),
                'mime_type' => mime_content_type($filePath)
            ];
        } catch (Exception $e) {
            $this->logger->error("Error retrieving file: " . $e->getMessage());
            return null;
        }
    }

    public function getFileInfo(string $filename, ?string $size = null): ?array
    {
        try {
            $filePath = $this->repository->getFilePath($filename, $size);

            if (!file_exists($filePath)) {
                return null;
            }

            $info = stat($filePath);

            return [
                'mime_type' => mime_content_type($filePath),
                'meta' => [
                    'Device' => $info[0],
                    'Inode' => $info[1],
                    'Permissions' => substr(sprintf('%o', $info[2]), -4), // Formata em octal
                    'Links' => $info[3],
                    'Owner (UID)' => $info[4],
                    'Group (GID)' => $info[5],
                    'Device Type' => $info[6],
                    'Size (bytes)' => $info[7],
                    'Last Access' => date('Y-m-d H:i:s', $info[8]),
                    'Last Modification' => date('Y-m-d H:i:s', $info[9]),
                    'Last Status Change' => date('Y-m-d H:i:s', $info[10]),
                ],
                'size' => filesize($filePath) . " bytes",
                'extra' => pathinfo($filePath)
            ];
        } catch (Exception $e) {
            $this->logger->error("Error retrieving file: " . $e->getMessage());
            return null;
        }
    }

    public function deleteFile(string $filename, ?string $size = null): bool
    {
        return $this->repository->deleteFilePath($filename, $size);   
    }

    private function processImageResizing(array $file, bool $isPublic, string $directory, string $filename, array $sizes): void
    {
        $basePath = $isPublic ? $this->config->getPublicDir() : $this->config->getPrivateDir();
        $originalFilePath = $this->repository->getDecodedPath($filename);
        [$dash, $name] = explode('-', $filename);
        $originalFullPath = $basePath . '/' . $originalFilePath;

        foreach ($sizes as $size) {
            $this->imageHandler->resize($originalFullPath, $size, $directory, hex2bin($name), $isPublic);
        }
    }
}