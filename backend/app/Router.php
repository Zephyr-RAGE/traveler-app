<?php

class Router
{
    private array $routes = [];
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $path, array $action): void
    {
        $this->routes['GET'][$path] = $action;
    }

    public function post(string $path, array $action): void
    {
        $this->routes['POST'][$path] = $action;
    }

    public function dispatch(string $method, string $uri): void
{
    if (!isset($this->routes[$method][$uri])) {
        http_response_code(404);
        echo '404 - Ruta no encontrada';
        return;
    }

    [$controller, $action] = $this->routes[$method][$uri];

    $instance = new $controller($this->config);

    $instance->$action();
}
}