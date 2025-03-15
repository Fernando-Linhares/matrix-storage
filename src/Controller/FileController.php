<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\ServerConfig;
use App\Logger\FileLogger;
use App\Service\FileService;
use App\Util\Attributes\Route;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Exception;

class FileController
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly ServerConfig $config,
        private readonly FileLogger $logger
    ) {
    }

    #[Route('POST', '/push')]
    public function push(Request $request, Response $response): void
    {
        try {
            $response->header('content-type', 'application/json');

            if (!isset($request->files['file'])) {
                $this->errorResponse($response, 'No file provided', 400, 'BAD_REQUEST');
                return;
            }

            $file = $request->files['file'];
            $isPublic = isset($request->post['visibility']) && $request->post['visibility'] === 'public';
            $appName = $request->post['appname'] ?? 'default';
            $sizes = $request->post['sizes'] ?? [];
            $tls = $_ENV['APP_TLS'];

            $result = $this->fileService->uploadFile($file, $isPublic, $appName, $sizes);

            $response->status(201);
            $response->end(
                json_encode(
                    [
                    'data' => [
                    'file_url' => "{$tls}://{$this->config->getHost()}:{$this->config->getPort()}/pull?filename={$result['filename']}",
                    'filename' => $result['filename'],
                    'datetime' => time(),
                    ]
                    ]
                )
            );
        } catch (Exception $e) {
            $this->errorResponse($response, $e->getMessage(), 500, 'INTERNAL_ERROR');
        }
    }

    #[Route('GET', '/pull')]
    public function pull(Request $request, Response $response): void
    {
        try {
            if (!isset($request->get['filename'])) {
                $this->errorResponse($response, 'Filename not provided', 400, 'BAD_REQUEST');
                return;
            }

            $filename = $request->get['filename'];
            $size = $request->get['size'] ?? null;

            $file = $this->fileService->getFile($filename, $size);

            if ($file === null) {
                $this->errorResponse($response, 'File not found', 404, 'NOT_FOUND');
                return;
            }

            $response->header('content-type', $file['mime_type']);
            $response->end($file['content']);
            
        } catch (Exception $e) {
            $this->errorResponse($response, $e->getMessage(), 500, 'INTERNAL_ERROR');
        }
    }

    #[Route('GET', '/info')]
    public function info(Request $request, Response $response): void
    {
        try {
            if (!isset($request->get['filename'])) {
                $this->errorResponse($response, 'Filename not provided', 400, 'BAD_REQUEST');
                return;
            }

            $filename = $request->get['filename'];
            $size = $request->get['size'] ?? null;

            $fileInfo = $this->fileService->getFileInfo($filename, $size);

            if ($fileInfo === null) {
                $this->errorResponse($response, 'File not found', 404, 'NOT_FOUND');
                return;
            }

            $response->header('content-type', 'application/json');
            $response->end(json_encode($fileInfo));
            
        } catch (Exception $e) {
            $this->errorResponse($response, $e->getMessage(), 500, 'INTERNAL_ERROR');
        }
    }

    #[Route('DELETE', '/delete')]
    public function delete(Request $request, Response $response): void
    {
        try {

            if (!isset($request->get['filename'])) {
                $this->errorResponse($response, 'Filename not provided', 400, 'BAD_REQUEST');
                return;
            }

            $filename = $request->get['filename'];
            $size = $request->get['size'] ?? null;

            $file = $this->fileService->getFile($filename, $size);

            if ($file === null) {
                $this->errorResponse($response, 'File not found', 404, 'NOT_FOUND');
                return;
            }
            if ($this->fileService->deleteFile($filename, $size)) {
                $response->status(201);
    
                $response->header('content-type', 'application/json');
                $response->end(
                    json_encode(
                        [
                        'removed' => [
                        'filename' => $filename,
                        'datetime' => time(),
                        ]
                        ]
                    )
                );
                return;
            }

            $this->errorResponse($response, 'Remove file error', 500, 'INTERNAL_ERROR');
            return;
        } catch (Exception $e) {
            $this->errorResponse($response, $e->getMessage(), 500, 'INTERNAL_ERROR');
        }
    }

    public function remove(Request $request, Response $response): void
    {
        try {
            if (!isset($request->get['filename'])) {
                $this->errorResponse($response, 'Filename not provided', 400, 'BAD_REQUEST');
                return;
            }

            $filename = $request->get['filename'];
            $size = $request->get['size'] ?? null;

            $file = $this->fileService->getFile($filename, $size);

            if ($file === null) {
                $this->errorResponse($response, 'File not found', 404, 'NOT_FOUND');
                return;
            }

            $response->header('content-type', $file['mime_type']);
            $response->end($file['content']);
        } catch (Exception $e) {
            $this->errorResponse($response, $e->getMessage(), 500, 'INTERNAL_ERROR');
        }
    }

    private function errorResponse(Response $response, string $message, int $status, string $code): void
    {
        $response->status($status);
        $response->header('content-type', 'application/json');
        $response->end(
            json_encode(
                [
                'error' => [
                'message' => $message,
                'code' => $code
                ]
                ]
            )
        );
        
        $this->logger->error("[{$code}]: {$message}");
    }
}