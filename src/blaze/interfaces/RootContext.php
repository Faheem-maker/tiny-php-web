<?php

namespace framework\web\blaze\interfaces;

class RootContext
{
    private static array $stack = [];

    public static function push(object $context)
    {
        self::$stack[] = $context;
    }

    public static function pop()
    {
        array_pop(self::$stack);
    }

    public static function find(string $class)
    {
        foreach (array_reverse(self::$stack) as $ctx) {
            if ($ctx instanceof $class) {
                return $ctx;
            }
        }

        return null;
    }
}