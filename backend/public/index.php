<?php

require_once __DIR__ . '/../config/env.php';

loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../config/cors.php';

handleCors();

if (filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN) === false) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');

    error_reporting(
        E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED
    );
}

require_once __DIR__ . '/../exceptions/ExceptionHandler.php';

function generateTraceId(): string
{
    $data = random_bytes(16);

    $data[6] = chr(
        (ord($data[6]) & 0x0f) | 0x40
    );

    $data[8] = chr(
        (ord($data[8]) & 0x3f) | 0x80
    );

    return vsprintf(
        '%s%s-%s-%s-%s-%s%s%s',
        str_split(bin2hex($data), 4)
    );
}

$traceId = generateTraceId();

ExceptionHandler::register($traceId);

$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../config/database.php';

$pdo = getDatabaseConnection();

require_once __DIR__ . '/../routes/web.php';