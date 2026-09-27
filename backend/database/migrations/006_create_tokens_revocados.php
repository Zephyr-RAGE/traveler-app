<?php

class CreateTokensRevocados
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tokens_revocados (
                jti VARCHAR(64) PRIMARY KEY,
                revoked_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
}