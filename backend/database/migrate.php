<?php

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/migrations/001_create_monedas.php';
require_once __DIR__ . '/migrations/002_create_paises.php';
require_once __DIR__ . '/migrations/003_create_ciudades.php';
require_once __DIR__ . '/migrations/004_create_usuarios.php';
require_once __DIR__ . '/migrations/005_create_historial.php';
require_once __DIR__ . '/migrations/006_create_tokens_revocados.php';
require_once __DIR__ . '/migrations/007_create_refresh_tokens.php';

$pdo = getDatabaseConnection();

$migrations = [
    new CreateMonedas(),
    new CreatePaises(),
    new CreateCiudades(),
    new CreateUsuarios(),
    new CreateHistorial(),
    new CreateTokensRevocados(),
    new CreateRefreshTokens(),
];

foreach ($migrations as $migration) {
    $migration->up($pdo);
}

echo "Migraciones ejecutadas correctamente." . PHP_EOL;