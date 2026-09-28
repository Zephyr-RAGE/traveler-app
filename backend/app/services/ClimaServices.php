<?php

require_once __DIR__ . '/../../exceptions/ApiException.php';

class ClimaServices
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) env(
            'WEATHER_API_KEY',
            ''
        );
    }

    public function obtenerClima(string $ciudad): ?array
    {
        try {
            $url = sprintf(
                'https://api.openweathermap.org/data/2.5/weather?q=%s&appid=%s&units=metric&lang=es',
                urlencode($ciudad),
                urlencode($this->apiKey)
            );

            $response = $this->request($url);

            if (
                !isset($response['main']) ||
                !is_array($response['main']) ||
                !isset($response['main']['temp']) ||
                !is_numeric($response['main']['temp'])
            ) {
                throw new ApiException(
                    502,
                    'EXTERNAL_API_ERROR'
                );
            }

            if (
                !isset($response['weather'][0]) ||
                !is_array($response['weather'][0]) ||
                !isset($response['weather'][0]['description'])
            ) {
                throw new ApiException(
                    502,
                    'EXTERNAL_API_ERROR'
                );
            }

            return [
                'temperatura' => (float) $response['main']['temp'],
                'descripcion' => $response['weather'][0]['description']
            ];

        } catch (ApiException $e) {
            return null;

        } catch (Throwable $e) {
            return null;
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
}