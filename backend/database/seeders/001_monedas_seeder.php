<?php

class MonedasSeeder
{
    public function run(PDO $pdo): void
    {
        $monedas = [
            ['GBP', 'Libra esterlina', '£'],
            ['JPY', 'Yen japonés', '¥'],
            ['INR', 'Rupia india', '₹'],
            ['DKK', 'Corona danesa', 'kr'],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO monedas (codigo, nombre, simbolo)
            VALUES (:codigo, :nombre, :simbolo)
            ON CONFLICT (codigo) DO NOTHING
        ");

        foreach ($monedas as $moneda) {
            $stmt->execute([
                ':codigo' => $moneda[0],
                ':nombre' => $moneda[1],
                ':simbolo' => $moneda[2],
            ]);
        }
    }
}