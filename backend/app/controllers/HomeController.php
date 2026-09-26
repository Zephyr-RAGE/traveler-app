<?php

class HomeController
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function index()
    {
        require __DIR__ . '/../views/home.php';
    }
}