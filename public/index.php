<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Container;
use App\Controllers\UserController;
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
        App\Entity\User::class => ['algorithm' => 'bcrypt'],
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

// Agrupar rutas bajo el prefijo '/users'
$router->group('/users', function (Router $router) {
    $router->addRoute('GET', '', ['UserController', 'index']); // /users
    $router->addRoute('GET', '/{id}', ['UserController', 'show']); // /users/{id}
    $router->addRoute('GET', '/create', ['UserController', 'create']); // /users/create
    $router->addRoute('POST', '', ['UserController', 'store']); // /users
    $router->addRoute('GET', '/{id}/edit', ['UserController', 'edit'], ['AuthMiddleware']); // /users/{id}/edit con middleware
    $router->addRoute('PUT', '/{id}', ['UserController', 'update']); // /users/{id}
    $router->addRoute('DELETE', '/{id}', ['UserController', 'destroy']); // /users/{id}
});

// Obtener la URI y el método de la petición
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Despachar la ruta
$router->dispatch($uri, $method);