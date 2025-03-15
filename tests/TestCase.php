<?php

namespace Tests;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase as FrameworkTestCase;
use Symfony\Component\Dotenv\Dotenv;

abstract class TestCase extends FrameworkTestCase
{
    protected static $http;

    protected function setUp(): void
    {
        $dotenv = new Dotenv();
        $dotenv->load(dirname(__DIR__, 1) . '/.env');

        self::$http = new Client([
            'base_uri' => '' . $_ENV['APP_URL'],
            'http_errors' => false
        ]);
    }
}