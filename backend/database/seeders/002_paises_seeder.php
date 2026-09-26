<?php

class PaisesSeeder
{
    public function run(PDO $pdo): void
    {
        $sql = "
            INSERT INTO paises (nombre, codigo, moneda_id)
            VALUES
                (
                    'Reino Unido',
                    'GB',
                    (SELECT id FROM monedas WHERE codigo = 'GBP')
                ),
                (
                    'Japón',
                    'JP',
                    (SELECT id FROM monedas WHERE codigo = 'JPY')
                ),
                (
                    'India',
                    'IN',
                    (SELECT id FROM monedas WHERE codigo = 'INR')
                ),
                (
                    'Dinamarca',
                    'DK',
                    (SELECT id FROM monedas WHERE codigo = 'DKK')
                )
            ON CONFLICT (codigo) DO NOTHING;
        ";

        $pdo->exec($sql);
    }
}