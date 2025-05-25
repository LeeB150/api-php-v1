<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Container;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Iniciar la sesión (si vas a usar AuthMiddleware)
session_start();

// Crear una instancia del Contenedor
$container = new Container();

// Registrar el PasswordHasherFactoryInterface en el Contenedor
$container->set(PasswordHasherFactoryInterface::class, function () {
    return new PasswordHasherFactory([
        App\Entity\User::class => ['algorithm' => 'auto'],
    ]);
});

// Registrar el UserPasswordHasherInterface en el Contenedor
$container->set(UserPasswordHasherInterface::class, function ($container) {
    $factory = $container->get(PasswordHasherFactoryInterface::class);
    if (!$factory) {
        throw new \Exception('PasswordHasherFactoryInterface no se encontró en el contenedor.');
    }
    return new UserPasswordHasher($factory);
});

// Crear una instancia del Router y pasarle el Contenedor
$router = new Router($container);

// Cargar rutas desde archivo externo
$routes = require __DIR__ . '/../config/routes.php';
$routes($router);


// Obtener la URI y el método de la petición
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Despachar la ruta
$router->dispatch($uri, $method);