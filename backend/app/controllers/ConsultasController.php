<?php

require_once __DIR__ . '/../services/TravelDataServices.php';
require_once __DIR__ . '/../../exceptions/ApiException.php';

class ConsultasController
{
    private PDO $pdo;
    private TravelDataServices $travelDataServices;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->travelDataServices = new TravelDataServices($pdo);
    }

    public function crear(?array $auth): void
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

        $usuarioId = (int) $auth['usuario']['id'];
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

        if (
            !$datos['conversion']['disponible']
        ) {
            throw new ApiException(
                502,
                'EXTERNAL_API_ERROR'
            );
        }

        $climaTexto = $datos['clima']['disponible']
            ? $datos['clima']['temperatura']
                . ' °C - '
                . $datos['clima']['descripcion']
            : 'Clima no disponible';

        $stmt = $this->pdo->prepare("
            INSERT INTO historial (
                usuario_id,
                ciudad_id,
                presupuesto_cop,
                clima,
                tasa,
                valor_convertido,
                fecha
            )
            VALUES (
                :usuario_id,
                :ciudad_id,
                :presupuesto_cop,
                :clima,
                :tasa,
                :valor_convertido,
                CURRENT_TIMESTAMP
            )
            RETURNING fecha
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':ciudad_id' => $ciudadId,
            ':presupuesto_cop' => $presupuestoCop,
            ':clima' => $climaTexto,
            ':tasa' => $datos['conversion']['tasa'],
            ':valor_convertido' => $datos['conversion']['valor_convertido']
        ]);

        $fecha = $stmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'data' => [
                'pais' => $datos['pais']['nombre'],
                'ciudad' => $datos['ciudad']['nombre'],
                'presupuesto_cop' => $presupuestoCop,
                'clima' => $climaTexto,
                'moneda' => [
                    'nombre' => $datos['moneda']['nombre'],
                    'simbolo' => $datos['moneda']['simbolo']
                ],
                'valor_convertido' => $datos['conversion']['valor_convertido'],
                'valor_convertido_con_simbolo' =>
                    $datos['moneda']['simbolo']
                    . $datos['conversion']['valor_convertido'],
                'tasa' => $datos['conversion']['tasa'],
                'fecha_tasa' => $fecha
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

    public function historial(?array $auth): void
{
    header('Content-Type: application/json; charset=utf-8');

    $usuarioId = (int) $auth['usuario']['id'];

    $stmt = $this->pdo->prepare("
        SELECT
            h.id,
            p.nombre AS pais,
            c.nombre AS ciudad,
            h.presupuesto_cop,
            h.clima,
            m.nombre AS moneda_nombre,
            m.simbolo AS moneda_simbolo,
            h.valor_convertido,
            h.tasa,
            h.fecha
        FROM historial h
        INNER JOIN ciudades c
            ON c.id = h.ciudad_id
        INNER JOIN paises p
            ON p.id = c.pais_id
        INNER JOIN monedas m
            ON m.id = p.moneda_id
        WHERE h.usuario_id = :usuario_id
        ORDER BY h.fecha DESC
        LIMIT 5
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId
    ]);

    $consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($consultas as &$consulta) {
        $consulta['presupuesto_cop'] =
            (float) $consulta['presupuesto_cop'];

        $consulta['valor_convertido'] =
            (float) $consulta['valor_convertido'];

        $consulta['tasa'] =
            (float) $consulta['tasa'];

        $consulta['valor_convertido_con_simbolo'] =
            $consulta['moneda_simbolo']
            . $consulta['valor_convertido'];
    }

    unset($consulta);

    echo json_encode([
        'success' => true,
        'data' => $consultas
    ], JSON_UNESCAPED_UNICODE);
}
}