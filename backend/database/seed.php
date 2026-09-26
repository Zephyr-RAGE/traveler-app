<?php

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/seeders/001_monedas_seeder.php';
require_once __DIR__ . '/seeders/002_paises_seeder.php';
require_once __DIR__ . '/seeders/003_ciudades_seeder.php';

$pdo = getDatabaseConnection();

$seeders = [
    new MonedasSeeder(),
    new PaisesSeeder(),
    new CiudadesSeeder(),
];

foreach ($seeders as $seeder) {
    $seeder->run($pdo);
}

echo "Seeders ejecutados correctamente." . PHP_EOL;