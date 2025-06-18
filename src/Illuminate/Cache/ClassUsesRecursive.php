<?php

namespace Illuminate\Cache;

class ClassUsesRecursive
{
    private static array $classes = [];

    /**
     * @param $className
     * @return array
     */
    public static function classUsesRecursive($className): array
    {
        if (! isset(self::$classes[$className])) {
            self::$classes[$className] = class_uses_recursive($className);
        }

        return self::$classes[$className];
    }

    /**
     * @param $needleClassName
     * @param $haystackClassName
     * @return bool
     */
    public static function inArray($needleClassName, $haystackClassName): bool
    {
        return in_array($needleClassName, self::classUsesRecursive($haystackClassName));
    }

    /**
     * @param $className
     */
    public static function remove($className): void
    {
        unset(self::$classes[$className]);
    }

    public static function removeAll(): void
    {
        self::$classes = [];
    }
}
