<?php

class CreateTasasCambio
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tasas_cambio (
                id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                moneda_origen VARCHAR(3) NOT NULL,
                moneda_destino VARCHAR(3) NOT NULL,
                tasa NUMERIC(18,8) NOT NULL,
                fecha TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
}