<?php

namespace App\Core;

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class Container
{
    protected $services = [];

    public function set(string $id, callable $callable): void
    {
        $this->services[$id] = $callable;
    }

    public function get(string $id)
    {
        if (isset($this->services[$id])) {
            return $this->services[$id]($this); // Pasar el contenedor para dependencias anidadas
        }

        return null; // O lanzar una excepción
    }
}