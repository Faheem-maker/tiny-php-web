<?php

namespace framework\web\routing;

use framework\contracts\routing\RouterInterface;
use framework\Component;
use framework\web\Route;
use framework\web\RouteGroup;

class Router extends Component implements RouterInterface
{
    protected $routes = [];
    protected $namedRoutes = [];
    protected $prefixStack = '';
    protected $groupStack = [];

    // Handle Grouping
    public function group($prefix, $callback)
    {
        $group = new RouteGroup($prefix);
        if (!empty($this->groupStack)) {
            end($this->groupStack)?->add($group);
        }
        $previousGroupStack = $this->prefixStack;
        $this->prefixStack .= $prefix;
        $this->groupStack[] = $group;
        $callback();
        $this->prefixStack = $previousGroupStack; // Reset after callback
        array_pop($this->groupStack);

        return $group;
    }

    public function route($route, $action, $method, $name = null)
    {
        $fullPath = $this->prefixStack . $route;
        $fullPath = app()->url->normalize($fullPath);

        // Convert {id} to a named regex group (?P<id>[^/]++)
        $pattern = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<$1>[^/]++)', $fullPath);
        $pattern = "#^" . $pattern . "$#";

        $routeObject = new Route($action, $fullPath, $name);
        $this->routes[$method][$pattern] = $routeObject;

        if (!empty($name)) {
            $this->namedRoutes[$name] = $this->routes[$method][$pattern];
        }
        if (!empty($this->groupStack)) {
            end($this->groupStack)->add($routeObject);
        }

        return $routeObject; // Return object to allow chaining ->name()
    }

    public function get($route, $action, $name = null)
    {
        return static::route($route, $action, 'GET', $name);
    }

    public function post($route, $action, $name = null)
    {
        return static::route($route, $action, 'POST', $name);
    }

    public function patch($route, $action, $name = null)
    {
        return static::route($route, $action, 'PATCH', $name);
    }

    public function put($route, $action, $name = null)
    {
        return static::route($route, $action, 'PUT', $name);
    }

    public function delete($route, $action, $name = null)
    {
        return static::route($route, $action, 'DELETE', $name);
    }

    public function resolve($uri, $method)
    {
        if (empty($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $pattern => $route) {
            if (preg_match($pattern, $uri, $matches)) {
                // Filter out non-string keys from preg_match to get clean params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return ['route' => $route, 'params' => $params];
            }
        }
        return null; // 404
    }

    public function resolveName($name, $params = [])
    {
        return $this->namedRoutes[$name];
    }

    public function name($name, $route)
    {
        $this->namedRoutes[$name] = $route;
    }

    public function rename($from, $to)
    {
        if (empty($this->namedRoutes[$from])) {
            return;
        }
        $this->namedRoutes[$to] = $this->namedRoutes[$from];
        unset($this->namedRoutes[$from]);
    }

    public function mount(string $prefix, RouterInterface $router)
    {
        $this->group($prefix, function () use ($router) {
            $this->routes = array_merge($this->routes, $router->routes());
            $this->namedRoutes = array_merge($this->namedRoutes, $router->namedRoutes());
        });
    }

    public function routes(): array
    {
        return $this->routes;
    }

    public function namedRoutes(): array
    {
        return $this->namedRoutes;
    }
}