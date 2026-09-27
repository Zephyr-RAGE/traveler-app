<?php

class Router
{
    private array $routes = [];
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get(
        string $path,
        array $action,
        bool $protected = false
    ): void {
        $this->routes['GET'][$path] = [
            'action' => $action,
            'protected' => $protected
        ];
    }

    public function post(
        string $path,
        array $action,
        bool $protected = false
    ): void {
        $this->routes['POST'][$path] = [
            'action' => $action,
            'protected' => $protected
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        if (!isset($this->routes[$method][$uri])) {
            http_response_code(404);
            echo '404 - Ruta no encontrada';
            return;
        }

        $route = $this->routes[$method][$uri];

        [$controller, $action] = $route['action'];

        $usuario = null;

        if ($route['protected']) {
            require_once __DIR__ . '/middleware/AuthTokenMiddleware.php';

            $middleware = new AuthTokenMiddleware($this->pdo);

            $usuario = $middleware->handle();
        }

        $instance = new $controller($this->pdo);

        $instance->$action($usuario);
    }
}