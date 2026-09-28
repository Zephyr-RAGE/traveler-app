<?php

require_once __DIR__ . '/PaisServices.php';
require_once __DIR__ . '/MonedaServices.php';
require_once __DIR__ . '/ClimaServices.php';
require_once __DIR__ . '/../../exceptions/ApiException.php';

class TravelDataServices
{
    private PaisServices $paisServices;
    private MonedaServices $monedaServices;
    private ClimaServices $climaServices;

    public function __construct(PDO $pdo)
    {
        $this->paisServices = new PaisServices($pdo);
        $this->monedaServices = new MonedaServices($pdo);
        $this->climaServices = new ClimaServices();
    }

    public function obtenerDatosCiudad(
        int $ciudadId,
        float $presupuestoCop
    ): ?array {
        $ciudad = $this->paisServices
            ->obtenerCiudadConMoneda($ciudadId);

        if ($ciudad === null) {
            return null;
        }

        /*
         * Clima
         */
        $clima = $this->climaServices->obtenerClima(
            $ciudad['nombre']
        );

        if ($clima === null) {
            $clima = [
                'disponible' => false,
                'mensaje' => 'Clima no disponible'
            ];
        } else {
            $clima['disponible'] = true;
        }

        /*
         * Conversión de moneda
         */
        $tasa = null;
        $valorConvertido = null;

        try {
            $tasa = $this->monedaServices->obtenerTasa(
                'COP',
                $ciudad['moneda_codigo']
            );

            if ($tasa !== null) {
                $valorConvertido = round(
                    $presupuestoCop * $tasa,
                    2
                );
            }
        } catch (ApiException $e) {
            $tasa = null;
            $valorConvertido = null;
        }

        $conversion = [
            'moneda_origen' => 'COP',
            'moneda_destino' => $ciudad['moneda_codigo'],
            'presupuesto_cop' => $presupuestoCop,
            'tasa' => $tasa,
            'valor_convertido' => $valorConvertido,
        ];

        if ($tasa === null) {
            $conversion['disponible'] = false;
            $conversion['mensaje'] = 'Conversión no disponible';
        } else {
            $conversion['disponible'] = true;
        }

        return [
            'ciudad' => [
                'id' => (int) $ciudad['id'],
                'nombre' => $ciudad['nombre'],
            ],

            'pais' => [
                'id' => (int) $ciudad['pais_id'],
                'nombre' => $ciudad['pais_nombre'],
                'codigo' => $ciudad['pais_codigo'],
            ],

            'moneda' => [
                'codigo' => $ciudad['moneda_codigo'],
                'nombre' => $ciudad['moneda_nombre'],
                'simbolo' => $ciudad['moneda_simbolo'],
            ],

            'clima' => $clima,

            'conversion' => $conversion,
        ];
    }
}