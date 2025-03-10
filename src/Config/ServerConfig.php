<?php

declare(strict_types=1);

namespace App\Config;

class ServerConfig
{
    private readonly string $host;
    private readonly int $port;
    private readonly string $resourceDir;
    private readonly string $logDir;

    public function __construct(array $config)
    {
        $this->host = $config['host'] ?? $_ENV('APP_HOST');
        $this->port = $config['port'] ?? $_ENV('APP_PORT');
        $this->resourceDir = $config['resourceDir'] ?? $_ENV('RESOURCE_DIR');
        $this->logDir = $config['logDir'] ?? $_ENV['LOG_DIR'];
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
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