<?php

require_once __DIR__ . '/env.php';

loadEnv(__DIR__ . '/../.env');

function getDatabaseConnection(): PDO
{
    $host = env('DB_HOST');
    $port = env('DB_PORT');
    $database = env('DB_NAME');
    $username = env('DB_USER');
    $password = env('DB_PASSWORD');

    $dsn = "pgsql:host=$host;port=$port;dbname=$database";

    $pdo = new PDO($dsn, $username, $password);

    return $pdo;
}