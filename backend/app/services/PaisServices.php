<?php

class PaisServices
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function obtenerPaises(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                id,
                nombre,
                codigo,
                moneda_id
            FROM paises
            ORDER BY nombre
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCiudadesPorPais(int $paisId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nombre,
                latitud,
                longitud
            FROM ciudades
            WHERE pais_id = :pais_id
            ORDER BY nombre
        ");

        $stmt->execute([
            ':pais_id' => $paisId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existePais(int $paisId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM paises
            WHERE id = :pais_id
            LIMIT 1
        ");

        $stmt->execute([
            ':pais_id' => $paisId
        ]);

        return (bool) $stmt->fetchColumn();
    }
}