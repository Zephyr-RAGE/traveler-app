<?php

require_once __DIR__ . '/../../exceptions/ApiException.php';

class MonedaServices
{
    private PDO $pdo;
    private string $apiKey;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->apiKey = (string) env(
            'EXCHANGE_API_KEY',
            ''
        );
    }

    public function obtenerTasa(
        string $monedaOrigen,
        string $monedaDestino
    ): ?float {
        $monedaOrigen = strtoupper($monedaOrigen);
        $monedaDestino = strtoupper($monedaDestino);

        try {
            $url = sprintf(
                'https://v6.exchangerate-api.com/v6/%s/pair/%s/%s',
                urlencode($this->apiKey),
                urlencode($monedaOrigen),
                urlencode($monedaDestino)
            );

            $response = $this->request($url);

            if (
                !isset($response['result']) ||
                $response['result'] !== 'success' ||
                !isset($response['conversion_rate']) ||
                !is_numeric($response['conversion_rate'])
            ) {
                throw new ApiException(
                    502,
                    'EXTERNAL_API_ERROR'
                );
            }

            $tasa = (float) $response['conversion_rate'];

            $this->guardarTasa(
                $monedaOrigen,
                $monedaDestino,
                $tasa
            );

            return $tasa;

        } catch (ApiException $e) {

            $ultimaTasa = $this->obtenerUltimaTasa(
                $monedaOrigen,
                $monedaDestino
            );

            if ($ultimaTasa !== null) {
                return $ultimaTasa;
            }

            throw $e;

        } catch (Throwable $e) {

            $ultimaTasa = $this->obtenerUltimaTasa(
                $monedaOrigen,
                $monedaDestino
            );

            if ($ultimaTasa !== null) {
                return $ultimaTasa;
            }

            throw new ApiException(
                502,
                'EXTERNAL_API_ERROR'
            );
        }
    }

    private function request(string $url): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ]
        ]);

        $body = curl_exec($ch);

        if ($body === false) {
            throw new ApiException(
                504,
                'EXTERNAL_API_TIMEOUT'
            );
        }

        $status = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        if ($status >= 500 || $status === 0) {
            throw new ApiException(
                502,
                'EXTERNAL_API_ERROR'
            );
        }

        $data = json_decode(
            $body,
            true
        );

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !is_array($data)
        ) {
            throw new ApiException(
                502,
                'EXTERNAL_API_ERROR'
            );
        }

        return $data;
    }

    private function guardarTasa(
        string $monedaOrigen,
        string $monedaDestino,
        float $tasa
    ): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO tasas_cambio (
                moneda_origen,
                moneda_destino,
                tasa
            )
            VALUES (
                :moneda_origen,
                :moneda_destino,
                :tasa
            )
        ");

        $stmt->execute([
            ':moneda_origen' => $monedaOrigen,
            ':moneda_destino' => $monedaDestino,
            ':tasa' => $tasa
        ]);
    }

    private function obtenerUltimaTasa(
        string $monedaOrigen,
        string $monedaDestino
    ): ?float {
        $stmt = $this->pdo->prepare("
            SELECT tasa
            FROM tasas_cambio
            WHERE moneda_origen = :moneda_origen
              AND moneda_destino = :moneda_destino
            ORDER BY fecha DESC
            LIMIT 1
        ");

        $stmt->execute([
            ':moneda_origen' => $monedaOrigen,
            ':moneda_destino' => $monedaDestino
        ]);

        $tasa = $stmt->fetchColumn();

        if ($tasa === false) {
            return null;
        }

        return (float) $tasa;
    }
}