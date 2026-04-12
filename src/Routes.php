<?php

namespace framework\web;

use framework\web\routing\Router;

class Routes
{
    protected static Router $router;

    protected static function router()
    {
        if (empty(static::$router)) {
            static::$router = app()->router;
        }
        return static::$router;
    }

    // Handle Grouping
    public static function group($prefix, $callback)
    {
        return static::router()->group($prefix, $callback);
    }

    public static function route($route, $action, $method, $name = null)
    {
        return static::router()->route($route, $action, $method, $name);
    }

    public static function get($route, $action, $name = null)
    {
        return static::route($route, $action, 'GET', $name);
    }

    public static function post($route, $action, $name = null)
    {
        return static::route($route, $action, 'POST', $name);
    }

    public static function patch($route, $action, $name = null)
    {
        return static::route($route, $action, 'PATCH', $name);
    }

    public static function put($route, $action, $name = null)
    {
        return static::route($route, $action, 'PUT', $name);
    }

    public static function delete($route, $action, $name = null)
    {
        return static::route($route, $action, 'DELETE', $name);
    }

    public static function resolve($uri, $method)
    {
        return static::router()->resolve($uri, $method);
    }

    public static function resolveName($name, $params = [])
    {
        return static::$router->resolveName($name, $params);
    }

    public static function resource($prefix, $controller, $config = [])
    {
        $resource = $config['resource'] ?? $prefix;

        $routes = [
            'index' => ['GET', '/'],
            'create' => ['GET', '/create'],
            'store' => ['POST', '/'],
            'show' => ['GET', "/{{$resource}}"],
            'edit' => ['GET', '/{' . $resource . '}/edit'],
            'update' => [['PUT', "/{{$resource}}"], ['PATCH', "/{{$resource}}"]],
            'destroy' => ['DELETE', "/{{$resource}}"],
        ];

        if (isset($config['only'])) {
            $routes = array_map(function ($route) use ($routes) {
                return $routes[$route];
            }, $config['only']);
        } else if (isset($config['except'])) {
            foreach ($config['except'] as $except) {
                unset($routes[$except]);
            }
        }

        return Routes::group($prefix, function () use ($routes, $controller) {
            foreach ($routes as $action => $route) {
                if (is_array($route[0])) {
                    foreach ($route as $r) {
                        $method = $r[0];
                        Routes::$method($r[1], [$controller, $action], $action);
                    }
                } else {
                    $method = $route[0];
                    Routes::$method($route[1], [$controller, $action], $action);
                }
            }
        })->name($resource);
    }

    public static function rename($from, $to)
    {
        static::router()->rename($from, $to);
    }
}