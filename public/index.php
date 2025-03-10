<?php

declare(strict_types=1);

require_once dirname(__DIR__, 1). '/vendor/autoload.php';

use App\Server\HttpServer;
use App\Config\ServerConfig;
use App\DI\Container;
use App\Controller\FileController;
use App\Service\FileService;
use App\Repository\FileRepository;
use App\Handler\{ImageHandler, VideoHandler};
use App\Validator\FileValidator;
use App\Logger\FileLogger;
use App\Util\PathEncoder;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__, 1).'/.env');

$container = new Container();

$container->register(ServerConfig::class, function () {
    return new ServerConfig([       
        'resourceDir' => dirname(__DIR__, 1) . '/resources',
        'logDir' => dirname(__DIR__, 1) . '/tmp/logs',
    ]);
});

$container->register(PathEncoder::class, function () {
    return new PathEncoder();
});

$container->register(FileValidator::class, function () {
    return new FileValidator([
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'image/bmp', 'image/tiff', 'image/svg+xml', 'video/mp4',
        'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm',
    ]);
});

$container->register(FileLogger::class, function ($container) {
    return new FileLogger($container->get(ServerConfig::class));
});

$container->register(ImageHandler::class, function ($container) {
    return new ImageHandler($container->get(FileLogger::class));
});

$container->register(VideoHandler::class, function ($container) {
    return new VideoHandler($container->get(FileLogger::class));
});

$container->register(FileRepository::class, function ($container) {
    return new FileRepository(
        $container->get(ServerConfig::class),
        $container->get(PathEncoder::class),
        $container->get(FileLogger::class)
    );
});

$container->register(FileService::class, function ($container) {
    return new FileService(
        $container->get(FileRepository::class),
        $container->get(FileValidator::class),
        $container->get(ImageHandler::class),
        $container->get(VideoHandler::class),
        $container->get(ServerConfig::class),
        $container->get(FileLogger::class)
    );
});

$container->register(FileController::class, function ($container) {
    return new FileController(
        $container->get(FileService::class),
        $container->get(ServerConfig::class),
        $container->get(FileLogger::class)
    );
});

$container->register(HttpServer::class, function ($container) {
    return new HttpServer(
        $container->get(ServerConfig::class),
        $container->get(FileController::class),
        $container->get(FileLogger::class)
    );
});

$server = $container->get(HttpServer::class);
$server->start();