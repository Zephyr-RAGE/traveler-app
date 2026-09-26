<?php

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        throw new RuntimeException('.env no encontrado');
    }

    $variables = parse_ini_file($path, false, INI_SCANNER_RAW);

    if ($variables === false) {
        throw new RuntimeException('No se pudo leer el archivo .env');
    }

    foreach ($variables as $key => $value) {
        putenv("$key=$value");
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    return $value;
}