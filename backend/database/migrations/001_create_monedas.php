<?php

class CreateMonedas
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS monedas (
                id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                codigo VARCHAR(3) NOT NULL UNIQUE,
                nombre VARCHAR(100) NOT NULL,
                simbolo VARCHAR(10) NOT NULL
            )
        ");
    }
}