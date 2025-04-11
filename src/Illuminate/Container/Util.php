<?php

namespace Illuminate\Container;

use Closure;
use Illuminate\Contracts\Container\ContextualAttribute;
use ReflectionAttribute;
use ReflectionNamedType;

/**
 * @internal
 */
class Util
{
    /**
     * @var array<string, array<class-string, \ReflectionClass>>
     */
    private static array $localCache;

    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * From Arr::wrap() in Illuminate\Support.
     *
     * @param  mixed  $value
     * @return array
     */
    public static function arrayWrap($value)
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Return the default value of the given value.
     *
     * From global value() helper in Illuminate\Support.
     *
     * @param  mixed  $value
     * @param  mixed  ...$args
     * @return mixed
     */
    public static function unwrapIfClosure($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }

    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * From Reflector::getParameterClassName() in Illuminate\Support.
     *
     * @param  \ReflectionParameter  $parameter
     * @return string|null
     */
    public static function getParameterClassName($parameter)
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

    /**
     * Get a contextual attribute from a dependency.
     *
     * @param  \ReflectionParameter  $dependency
     * @return \ReflectionAttribute|null
     */
    public static function getContextualAttributeFromDependency($dependency)
    {
        return $dependency->getAttributes(ContextualAttribute::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
    }

    /**
     * This has been extracted from API Platform
     *
     * @param string $directory
     *
     * @return array<class-string, \ReflectionClass>
     */
    public static function getReflectionClassesFromDirectory(string $directory): array
    {
        $id = hash('xxh3', $directory);
        if (isset(self::$localCache[$id])) {
            return self::$localCache[$id];
        }

        $includedFiles = [];
        $iterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '/^.+\.php$/i',
            \RecursiveRegexIterator::GET_MATCH
        );

        foreach ($iterator as $file) {
            $sourceFile = $file[0];

            if (!preg_match('(^phar:)i', (string) $sourceFile)) {
                $sourceFile = realpath($sourceFile);
            }

            try {
                require_once $sourceFile;
            } catch (\Throwable) {
                // invalid PHP file (example: missing parent class)
                continue;
            }

            $includedFiles[$sourceFile] = true;
        }

        $declared = array_merge(get_declared_classes(), get_declared_interfaces());
        $ret = [];
        foreach ($declared as $className) {
            $reflectionClass = new \ReflectionClass($className);
            $sourceFile = $reflectionClass->getFileName();
            if (isset($includedFiles[$sourceFile])) {
                $ret[$className] = $reflectionClass;
            }
        }

        return self::$localCache[$id] = $ret;
    }
}
