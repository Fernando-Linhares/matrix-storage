<?php

declare(strict_types=1);

namespace App\Config;

class ServerConfig
{
    private readonly string $host;
    private readonly string $port;
    private readonly string $resourceDir;
    private readonly string $logDir;

    public function __construct()
    {
        $this->host = $_ENV['APP_HOST'] ?? 'localhost';
        $this->port = $_ENV['APP_PORT'] ?? '8727';
        $this->resourceDir = $_ENV['RESOURCE_DIR'] ?? dirname(__DIR__, 2) . '/resources';
        $this->logDir = $_ENV['LOG_DIR'] ?? dirname(__DIR__, 1) . '/tmp/logs';
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return intval($this->port);
    }

    public function getResourceDir(): string
    {
        return $this->resourceDir;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }

    public function getPublicDir(): string
    {
        return $this->resourceDir . '/public';
    }

    public function getPrivateDir(): string
    {
        return $this->resourceDir . '/private';
    }

    public function getErrorLogPath(): string
    {
        return $this->logDir . '/error_logs';
    }
}