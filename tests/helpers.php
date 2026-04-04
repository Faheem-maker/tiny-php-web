<?php

use framework\components\Config;
use framework\components\PathManager;
use framework\components\Validator;
use framework\web\tests\FakeFileSystem;
use framework\web\tests\TestDependencyContainer;
use framework\web\WebApplication;

function createApp(array $config = [])
{
    $base_config = [
        'paths' => [
            'base_dir' => __DIR__,
            'root' => __DIR__,
            'runtime' => __DIR__ . '/runtime',
            'assets' => __DIR__ . '/app/resources',
            'storage' => __DIR__ . '/storage'
        ],
        'TEST_KEY' => 'tests',
    ];

    $base_config = array_merge_recursive($base_config, $config);

    $app = WebApplication::getInstance('/', 'GET');

    $app->registerComponent('config', new Config());
    $app->registerComponent('path', new PathManager());
    $app->registerComponent('di', new TestDependencyContainer());
    $app->registerComponent('validator', new Validator());
    $app->registerComponent('fs', new FakeFileSystem());

    foreach ($base_config as $key => $value) {
        $app->config->set($key, $value);
    }

    $app->init();

    return $app;
}

function app()
{
    return WebApplication::get();
}

/**
 * @return framework\db\QueryBuilder|null
 */
function db()
{
    return app()->db;
}

function config($key = null, $default = null)
{
    if ($key === null) {
        return app()->config;
    }

    return app()->config->get($key, $default);
}

function env($key, $default = null)
{
    if ($key === null) {
        return $_ENV;
    }
    return $_ENV[$key] ?? $default;
}

function logs()
{
    return app()->logger;
}