<?php

require_once __DIR__ . '/../app/Router.php';
require_once __DIR__ . '/../app/controllers/HomeController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';


$router = new Router($pdo);

$router->get('/', [HomeController::class, 'index']);

$router->post('/api/auth/register', [AuthController::class, 'register']);

$router->post('/api/auth/login', [AuthController::class, 'login']);

$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);