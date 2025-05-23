<?php

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $currentGroupPrefix = '';
    protected $currentMiddlewares = [];
    protected $container;

    public function __construct(\App\Core\Container $container)
    {
        $this->container = $container;
    }

    public function group($prefix, $callback, $middlewares = [])
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $previousMiddlewares = $this->currentMiddlewares;

        $this->currentGroupPrefix .= '/' . trim($prefix, '/');
        $this->currentMiddlewares = array_merge($this->currentMiddlewares, $middlewares);

        call_user_func($callback, $this);

        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentMiddlewares = $previousMiddlewares;
    }

    public function addRoute($method, $uri, $callback, $middlewares = [])
    {
        $path = $this->currentGroupPrefix;
        if (!empty(trim($uri, '/'))) {
            $path .= '/' . trim($uri, '/');
        }

        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';

        $this->routes[$method][$pattern] = [
            'callback' => $callback,
            'middlewares' => array_merge($this->currentMiddlewares, $middlewares)
        ];
    }

    public function dispatch($uri, $method)
    {
        $uri = '/' . trim($uri, '/');
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $pattern => $routeData) {
                if (preg_match($pattern, $uri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $callback = $routeData['callback'];
                    $middlewares = $routeData['middlewares'];

                    // Ejecutar middlewares
                    foreach ($middlewares as $middlewareClass) {
                        $middlewareFullName = 'App\\Middlewares\\' . $middlewareClass;
                        if (class_exists($middlewareFullName)) {
                            $middleware = new $middlewareFullName();
                            if (method_exists($middleware, 'handle')) {
                                if (!$middleware->handle()) {
                                    return; // Si un middleware devuelve false, se detiene la petición
                                }
                            }
                        }
                    }

                    // Ejecutar el callback (controlador o función)
                    if (is_callable($callback)) {
                        call_user_func_array($callback, $params);
                        return;
                    }

                    if (is_array($callback) && count($callback) === 2) {
                        $controllerName = 'App\\Controllers\\' . $callback[0];
                        $methodName = $callback[1];

                        if (class_exists($controllerName)) {
                            $reflectionClass = new \ReflectionClass($controllerName);
                            $constructor = $reflectionClass->getConstructor();
                            $controller = null;

                            if ($constructor !== null) {
                                $paramsToInject = [];
                                foreach ($constructor->getParameters() as $param) {
                                    $paramType = $param->getType();
                                    if ($paramType !== null && !$paramType->isBuiltin()) {
                                        $dependencyClassName = (string) $paramType;
                                        $dependency = $this->container->get($dependencyClassName);
                                        if ($dependency === null) {
                                            throw new \Exception("No se pudo resolver la dependencia: " . $dependencyClassName . " para el controlador " . $controllerName);
                                        }
                                        $paramsToInject[] = $dependency;
                                    }
                                }
                                $controller = $reflectionClass->newInstanceArgs($paramsToInject);
                            } else {
                                $controller = new $controllerName();
                            }

                            if (method_exists($controller, $methodName)) {
                                call_user_func_array([$controller, $methodName], $params);
                                return;
                            }
                        }
                    }
                    return; // Importante: Detener la ejecución después de encontrar y ejecutar la ruta
                }
            }
        }
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
    }
}