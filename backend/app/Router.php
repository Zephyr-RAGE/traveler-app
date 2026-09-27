<?php

require_once __DIR__ . '/../exceptions/ApiException.php';

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
        $route = null;
        $params = [];

        // 1. Buscar coincidencia exacta
        if (isset($this->routes[$method][$uri])) {
            $route = $this->routes[$method][$uri];
        } else {
            // 2. Buscar rutas con parámetros dinámicos
            foreach ($this->routes[$method] ?? [] as $path => $registeredRoute) {
                $pattern = preg_replace(
                    '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                    '([^/]+)',
                    $path
                );

                $pattern = '#^' . $pattern . '$#';

                if (preg_match($pattern, $uri, $matches)) {
                    $route = $registeredRoute;

                    preg_match_all(
                        '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                        $path,
                        $parameterNames
                    );

                    foreach ($parameterNames[1] as $index => $name) {
                        $params[$name] = $matches[$index + 1];
                    }

                    break;
                }
            }
        }

        // 3. Si no existe la ruta
        if ($route === null) {
            if (str_starts_with($uri, '/api/')) {
                throw new ApiException(
                    404,
                    'NOT_FOUND'
                );
            }

            http_response_code(404);
            echo '404 - Ruta no encontrada';
            return;
        }

        [$controller, $action] = $route['action'];

        $usuario = null;

        // 4. Middleware
        if ($route['protected']) {
            require_once __DIR__ . '/middleware/AuthTokenMiddleware.php';

            $middleware = new AuthTokenMiddleware($this->pdo);

            $usuario = $middleware->handle();
        }

        // 5. Controller
        $instance = new $controller($this->pdo);

        $instance->$action(
            $usuario,
            ...array_values($params)
        );
    }
}