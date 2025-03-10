<?php

declare(strict_types=1);

namespace App\Server;

use App\Config\ServerConfig;
use App\Controller\FileController;
use App\Logger\FileLogger;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

class HttpServer
{
    public function __construct(
        private readonly ServerConfig $config,
        private readonly FileController $fileController,
        private readonly FileLogger $logger
    ) {}

    public function start(): void
    {
        $server = new Server($this->config->getHost(), $this->config->getPort());

        $server->on('start', function ($server) {
            $tls = $_ENV['APP_TLS'];

            $this->logger->info("SWOOLE Http Server is running and started at: {$tls}://{$this->config->getHost()}:{$this->config->getPort()}");
        });

        $server->on('request', function (Request $request, Response $response) {
            $uri = $request->server['request_uri'];
            $method = $request->getMethod();

            if ($method === 'POST' && $uri === '/push') {
                $this->fileController->push($request, $response);
                return;
            }

            if ($method === 'GET' && $uri === '/pull') {
                $this->fileController->pull($request, $response);
                return;
            }

            $this->fileController->notFound($response);
        });

        $server->start();
    }
}