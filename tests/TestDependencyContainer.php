<?php

namespace framework\web\tests;

use Closure;
use Exception;
use framework\Component;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;

class TestDependencyContainer extends Component
{
    private array $instances = [];
    private array $definitions = [];
    private ?Closure $fallbackHandler = null;

    /**
     * Register a fallback handler for unknown dependencies or type mismatches.
     * The callback signature: function(string $name, ?string $type)
     */
    public function setFallback(callable $handler): void
    {
        $this->fallbackHandler = Closure::fromCallable($handler);
    }

    public function singleton(string $id, $concrete = null): void
    {
        $this->definitions[$id] = [
            'concrete' => $concrete ?? $id,
            'shared' => true
        ];
    }

    public function scoped(string $id, $concrete = null): void
    {
        $this->definitions[$id] = [
            'concrete' => $concrete ?? $id,
            'shared' => false
        ];
    }

    /**
     * Resolve a dependency. 
     * Modified: Removed automated instantiation of unregistered strings.
     */
    public function get(string $id, array $parameters = [])
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Only proceed if the ID is explicitly registered
        if (!isset($this->definitions[$id])) {
            return null;
        }

        $definition = $this->definitions[$id];
        $concrete = $definition['concrete'];

        if ($concrete instanceof Closure) {
            $object = $concrete($this, ...$parameters);
        } elseif (is_string($concrete) && class_exists($concrete)) {
            // Note: We still allow 'make' here because it's the registered concrete, 
            // but the container no longer "guesses" classes in the fallback.
            $object = $this->make($concrete, $parameters);
        } else {
            $object = $concrete;
        }

        if ($definition['shared']) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    public function make(string $className, array $parameters = [])
    {
        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new Exception("Class {$className} is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if (is_null($constructor)) {
            return new $className;
        }

        $dependencies = $this->resolveDependencies($constructor, $parameters);
        return $reflection->newInstanceArgs($dependencies);
    }

    public function invoke(object $instance, string $method, array $parameters = [])
    {
        $reflectionMethod = new ReflectionMethod($instance, $method);
        $dependencies = $this->resolveDependencies($reflectionMethod, $parameters);
        return $reflectionMethod->invokeArgs($instance, $dependencies);
    }

    /**
     * The heart of the container.
     * Modified: Logic updated for explicit resolution and fallback handler.
     */
    private function resolveDependencies(ReflectionFunctionAbstract $method, array $parameters): array
    {
        $resolved = [];

        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            $typeName = ($type instanceof ReflectionNamedType) ? $type->getName() : null;

            // 1. Manual parameter check WITH Type Validation
            if (array_key_exists($name, $parameters)) {
                $providedValue = $parameters[$name];

                if ($this->isValidType($providedValue, $type)) {
                    $resolved[] = $providedValue;
                    continue;
                }
                // If type doesn't match, we DON'T continue. 
                // We allow the Fallback Handler a chance to "convert" it.
            }

            // 2. Resolve via Class/Interface type hint (Explicit only)
            if ($typeName && !$type->isBuiltin()) {
                $instance = $this->get($typeName);
                if ($instance !== null) {
                    $resolved[] = $instance;
                    continue;
                }
            }

            // 3. Fallback Handler (Your logic for "User $user" goes here)
            // We pass the provided parameters so the fallback knows the context (like an ID)
            if ($this->fallbackHandler) {
                $fallbackValue = ($this->fallbackHandler)($name, $typeName, $parameters);
                if ($fallbackValue !== null) {
                    // Final sanity check: Does the fallback return what the type hint requires?
                    if ($this->isValidType($fallbackValue, $type)) {
                        $resolved[] = $fallbackValue;
                        continue;
                    }
                }
            }

            // 4. Fallback to default value
            if ($parameter->isDefaultValueAvailable()) {
                $resolved[] = $parameter->getDefaultValue();
                continue;
            }

            throw new Exception("Cannot resolve parameter '{$name}' of type '{$typeName}' for {$method->getName()}.");
        }

        return $resolved;
    }

    /**
     * Helper to check if a value matches a ReflectionType
     */
    private function isValidType($value, ?ReflectionType $type): bool
    {
        if ($type === null)
            return true;
        if ($type->allowsNull() && $value === null)
            return true;

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();
            if ($type->isBuiltin()) {
                $gettype = gettype($value);
                $map = ['integer' => 'int', 'boolean' => 'bool', 'double' => 'float'];
                $currentType = $map[$gettype] ?? $gettype;
                return $currentType === $typeName;
            }
            return $value instanceof $typeName;
        }
        return true;
    }
}