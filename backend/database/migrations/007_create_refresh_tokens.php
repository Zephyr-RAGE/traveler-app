<?php

class CreateRefreshTokens
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS refresh_tokens (
                id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                usuario_id BIGINT NOT NULL,
                token_hash VARCHAR(64) NOT NULL UNIQUE,
                expires_at TIMESTAMPTZ NOT NULL,
                revoked_at TIMESTAMPTZ NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_refresh_tokens_usuario
                    FOREIGN KEY (usuario_id)
                    REFERENCES usuarios(id)
            )
        ");
    }
}