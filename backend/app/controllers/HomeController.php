<?php

class HomeController
{
    public function __construct(PDO $pdo)
    {
    }

    public function index(): void
    {
        require __DIR__ . '/../views/home.php';
    }
}