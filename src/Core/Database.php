<?php

namespace App\Core;

use PDO;

class Database
{
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/database.php';

        $dsn = $this->config['driver'] . ':host=' . $this->config['host'] . ';dbname=' . $this->config['database'] . ';charset=' . $this->config['charset'];

        try {
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
        } catch (\PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }

    // Evitar la clonación de la instancia
    private function __clone() {}

    // Evitar la deserialización de la instancia
    public function __wakeup() {}
}