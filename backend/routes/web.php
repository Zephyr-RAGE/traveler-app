<?php

require_once __DIR__ . '/../app/Router.php';
require_once __DIR__ . '/../app/controllers/HomeController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/PaisController.php';
require_once __DIR__ . '/../app/controllers/TravelDataController.php';
require_once __DIR__ . '/../app/controllers/ConsultasController.php';

$router = new Router($pdo);

$router->get('/', [HomeController::class, 'index']);

$router->post(
    '/api/auth/register',
    [AuthController::class, 'register']
);

$router->post(
    '/api/auth/login',
    [AuthController::class, 'login']
);

$router->post(
    '/api/auth/logout',
    [AuthController::class, 'logout'],
    true
);

$router->get(
    '/api/auth/me',
    [AuthController::class, 'me'],
    true
);

$router->get(
    '/api/paises',
    [PaisController::class, 'index'],
    true
);

$router->post(
    '/api/travel-data',
    [TravelDataController::class, 'obtenerDatosCiudad'],
    true
);

$router->post(
    '/api/consultas',
    [ConsultasController::class, 'crear'],
    true
);

$router->get(
    '/api/consultas/historial',
    [ConsultasController::class, 'historial'],
    true
);

$router->get(
    '/api/paises/{id}/ciudades',
    [PaisController::class, 'ciudades'],
    true
);

$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);