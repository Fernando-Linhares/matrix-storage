<?php

declare(strict_types=1);

namespace App\Server;

use App\Config\ServerConfig;
use App\Controller\FileController;
use App\DI\Container;
use App\Logger\FileLogger;
use App\Util\Attributes\Route;
use ReflectionClass;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

class HttpServer
{
    private array $controllers = [
        FileController::class
    ];

    public function __construct(
        private readonly ServerConfig $config,
        private readonly FileLogger $logger
    ) {
    }

    public function start(): void
    {
        $server = new Server($this->config->getHost(), $this->config->getPort());

        $server->on(
            'start', function ($server) {
                $tls = $_ENV['APP_TLS'];
                $this->logger->info("SWOOLE Http Server is running and started at: {$tls}://{$this->config->getHost()}:{$this->config->getPort()}");
            }
        );

        $routes = [];

        foreach ($this->controllers as $controllerName) {
            $controller = new ReflectionClass($controllerName);

            foreach ($controller->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class);

                foreach ($attributes as $attribute) {
                    $routes[] = [$attribute->newInstance(), $controllerName, $method->name];
                }
            }
        }

        $server->on(
            'request', function (Request $request, Response $response) use ($routes) {
                $currentUri = $request->server['request_uri'];
                $currentMethod = $request->getMethod();

                foreach ($routes as $route) {
                    [$route, $controller, $action] = $route;

                    if($route->getMethod() === $currentMethod && $route->getUri() === $currentUri) {
                        $container = new Container();
                        $instance = $container->get($controller);
                        call_user_func([$instance, $action], $request, $response);
                        return;
                    }
                }

                $this->notFound($response);
            }
        );

        $server->start();
    }

    public function notFound(Response $response)
    {
        $response->header('content-type', 'application/json');
        $response->end(json_encode(['data' => 'not found']));
    }
}