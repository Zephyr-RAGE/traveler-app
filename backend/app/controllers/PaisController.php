<?php

require_once __DIR__ . '/../services/PaisServices.php';
require_once __DIR__ . '/../../exceptions/ApiException.php';

class PaisController
{
    private PDO $pdo;
    private PaisServices $paisServices;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->paisServices = new PaisServices($pdo);
    }

    public function index(?array $auth): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $paises = $this->paisServices->obtenerPaises();

        echo json_encode([
            'success' => true,
            'data' => $paises
        ], JSON_UNESCAPED_UNICODE);
    }

    public function ciudades(?array $auth, string $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!ctype_digit($id)) {
            throw new ApiException(
                404,
                'NOT_FOUND'
            );
        }

        $paisId = (int) $id;

        if (!$this->paisServices->existePais($paisId)) {
            throw new ApiException(
                404,
                'NOT_FOUND'
            );
        }

        $ciudades = $this->paisServices
            ->obtenerCiudadesPorPais($paisId);

        echo json_encode([
            'success' => true,
            'data' => $ciudades
        ], JSON_UNESCAPED_UNICODE);
    }
}