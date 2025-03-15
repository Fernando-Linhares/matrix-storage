<?php

declare(strict_types=1);

namespace App\Logger;

use App\Config\ServerConfig;
use DateTime;

class FileLogger
{
    private const GREEN = "\e[1;32m";
    private const BLUE = "\e[1;34m";
    private const RED = "\e[1;31m";
    private const YELLOW = "\e[1;33m";
    private const RESET = "\e[0m";

    public function __construct(
        private readonly ServerConfig $config
    ) {
    }

    public function info(string $message): void
    {
        $datetime = $this->getDateTime();
        $formattedMessage = self::GREEN . "[INFO|{$datetime}]:" . self::RESET . self::BLUE . " {$message} " . self::RESET . PHP_EOL;
        echo $formattedMessage;
    }

    public function error(string $message): void
    {
        $datetime = $this->getDateTime();
        $formattedMessage = self::RED . "[ERROR|{$datetime}]: {$message} " . self::RESET . PHP_EOL;
        echo $formattedMessage;
        
        $this->writeToErrorLog($formattedMessage);
    }

    public function warning(string $message): void
    {
        $datetime = $this->getDateTime();
        $formattedMessage = self::YELLOW . "[WARNING|{$datetime}]: {$message} " . self::RESET . PHP_EOL;
        echo $formattedMessage;
    }

    private function getDateTime(): string
    {
        return (new DateTime())->format('Y-m-d H:i:s');
    }

    private function writeToErrorLog(string $message): void
    {
        $logPath = $this->config->getErrorLogPath();
        $dirName = dirname($logPath);
        
        if (!is_dir($dirName)) {
            mkdir($dirName, 0755, true);
        }
        
        file_put_contents($logPath, $message, FILE_APPEND);
    }
}