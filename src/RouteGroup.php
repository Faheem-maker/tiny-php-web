<?php

namespace framework\web;

class RouteGroup {
    protected array $routes = [];
    protected string $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function add(Route|RouteGroup $route) {
        $this->routes[] = $route;
    }

    public function middleware($middleware) {
        foreach ($this->routes as $route) {
            $route->middleware($middleware);
        }

        return $this;
    }

    public function name($name) {
        foreach ($this->routes as $route) {
            $route->rename($name . '.' . $route->name);
        }

        return $this;
    }

    public function rename($name) {
        return $this->name($name);
    }
}