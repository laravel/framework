<?php

namespace Illuminate\Foundation\Support;

use ReflectionClass;
use ReflectionException;
use SplFileInfo;

abstract class DiscoversClasses
{
    /**
     * Get all of the classes by searching the given directory.
     *
     * @param  string  $path
     * @return array
     */
    abstract public static function within($path);

    /**
     * Get all of the classes from an array of files.
     *
     * @param  iterable  $files
     * @return array
     */
    public static function discoverClasses($files)
    {
        $discovered = [];

        foreach ($files as $file) {
            try {
                $instance = new ReflectionClass(
                    static::classFromFile($file)
                );
            } catch (ReflectionException) {
                continue;
            }

            if (! $instance->isInstantiable()) {
                continue;
            }

            $discovered[] = $instance;
        }

        return $discovered;
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param  \SplFileInfo  $file
     * @return string
     */
    protected static function classFromFile(SplFileInfo $file)
    {
        $contents = file_get_contents($file->getRealPath());

        if (! $contents) {
            return $file->getRealPath();
        }

        return str($contents)->after('namespace ')->before(";\n")
            ->append("\\{$file->getFilename()}")
            ->replaceLast('.php', '')
            ->toString();
    }
}
