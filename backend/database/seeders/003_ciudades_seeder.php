<?php

class CiudadesSeeder
{
    public function run(PDO $pdo): void
    {
        $sql = "
            INSERT INTO ciudades (pais_id, nombre, latitud, longitud)
            VALUES
                (
                    (SELECT id FROM paises WHERE codigo = 'GB'),
                    'Londres',
                    51.5074,
                    -0.1278
                ),
                (
                    (SELECT id FROM paises WHERE codigo = 'GB'),
                    'Manchester',
                    53.4808,
                    -2.2426
                ),
                (
                    (SELECT id FROM paises WHERE codigo = 'JP'),
                    'Tokio',
                    35.6762,
                    139.6503
                ),
                (
                    (SELECT id FROM paises WHERE codigo = 'JP'),
                    'Osaka',
                    34.6937,
                    135.5023
                ),
                (
                    (SELECT id FROM paises WHERE codigo = 'IN'),
                    'Nueva Delhi',
                    28.6139,
                    77.2090
                ),
                (
                    (SELECT id FROM paises WHERE codigo = 'IN'),
                    'Mumbai',
                    19.0760,
                    72.8777
                ),
                (
                    (SELECT id FROM paises WHERE codigo = 'DK'),
                    'Copenhague',
                    55.6761,
                    12.5683
                ),
                (
                    (SELECT id FROM paises WHERE codigo = 'DK'),
                    'Aarhus',
                    56.1629,
                    10.2039
                )
            ON CONFLICT DO NOTHING;
        ";

        $pdo->exec($sql);
    }
}