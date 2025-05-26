<?php
use App\Core\Router;

return function($router) {
    $router->group('/users', function (Router $router) {
        $router->addRoute('GET', '/list', ['UserController', 'index']);
        $router->addRoute('GET', '/{id}', ['UserController', 'show']);
        $router->addRoute('GET', '/create', ['UserController', 'create']);
        $router->addRoute('POST', '', ['UserController', 'store']);
        $router->addRoute('GET', '/{id}/edit', ['UserController', 'edit'], ['AuthMiddleware']);
        $router->addRoute('PUT', '/{id}', ['UserController', 'update']);
        $router->addRoute('DELETE', '/{id}', ['UserController', 'destroy']);
    });
};