<?php

// CREATE DATABASE api_copilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'api_copilot',
    'username' => 'root',
    'password' => '',
    'port' => '3306',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];