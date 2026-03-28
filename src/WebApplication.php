<?php

namespace framework\web;

use framework\Application;
use framework\routing\Router;

/**
 * The base class for all applications.
 * It supports component binding
 * and singleton.
 * 
 * Known Components
 * @property web\components\Config $config Configuration component
 * @property web\components\PathManager $path Path manager component
 * @property web\components\UrlManager $url URL manager component
 * @property web\components\AssetManager $assets Asset manager component
 * @property web\components\WidgetManager $widgets Widget manager component
 * @property web\components\DependencyContainer $di Dependency injection container component
 * @property Router $router
 */
class WebApplication extends Application
{
    public string $route;
    public string $method;

    /**
     * Private constructor to enforce singleton
     */
    private function __construct($route, $method)
    {
        $this->route = $route;
        $this->method = $method;
    }

    public function run()
    {
        $executor = new Executor($this->router);

        $executor->execute($this->url->path(), $this->method);
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(string $route, string $method): Application
    {
        if (static::$instance === null) {
            static::$instance = new static($route, $method);
        }

        return static::$instance;
    }
}