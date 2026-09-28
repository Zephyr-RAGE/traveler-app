<?php

require_once __DIR__ . '/../services/TravelDataServices.php';
require_once __DIR__ . '/../../exceptions/ApiException.php';

class TravelDataController
{
    private TravelDataServices $travelDataServices;

    public function __construct(PDO $pdo)
    {
        $this->travelDataServices = new TravelDataServices($pdo);
    }

    public function obtenerDatosCiudad(?array $auth): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $body = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($body)) {
            throw new ApiException(
                400,
                'BAD_REQUEST'
            );
        }

        if (
            !isset($body['ciudad_id']) ||
            !isset($body['presupuesto_cop'])
        ) {
            throw new ApiException(
                422,
                'VALIDATION_ERROR'
            );
        }

        if (
            !is_int($body['ciudad_id']) &&
            !ctype_digit((string) $body['ciudad_id'])
        ) {
            throw new ApiException(
                422,
                'VALIDATION_ERROR'
            );
        }

        if (
            !is_numeric($body['presupuesto_cop']) ||
            (float) $body['presupuesto_cop'] <= 0
        ) {
            throw new ApiException(
                422,
                'VALIDATION_ERROR'
            );
        }

        $ciudadId = (int) $body['ciudad_id'];
        $presupuestoCop = (float) $body['presupuesto_cop'];

        $datos = $this->travelDataServices
            ->obtenerDatosCiudad(
                $ciudadId,
                $presupuestoCop
            );

        if ($datos === null) {
            throw new ApiException(
                404,
                'NOT_FOUND'
            );
        }

        echo json_encode([
            'success' => true,
            'data' => $datos
        ], JSON_UNESCAPED_UNICODE);
    }
}