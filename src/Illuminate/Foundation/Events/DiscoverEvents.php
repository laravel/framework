<?php

namespace Illuminate\Foundation\Events;

use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DiscoverEvents
{
    /**
     * Get all of the events and listeners by searching the given listener directory.
     *
     * @param  string  $listenerPath
     * @param  string  $basePath
     * @return array
     */
    public static function within($listenerPath, $basePath)
    {
        return collect(static::getListenerEvents(
            (new Finder)->files()->in($listenerPath), $basePath
        ))->mapToDictionary(function ($event, $listener) {
            return [$event => $listener];
        })->all();
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $listeners
     * @param  string  $basePath
     * @return array
     */
    protected static function getListenerEvents($listeners, $basePath)
    {
        $listenerEvents = [];

        foreach ($listeners as $listener) {
            try {
                $listener = new ReflectionClass(
                    static::classFromFile($listener, $basePath)
                );
            } catch (ReflectionException $e) {
                continue;
            }

            if (! $listener->isInstantiable()) {
                continue;
            }

            foreach ($listener->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! Str::is('handle*', $method->name) ||
                    ! isset($method->getParameters()[0])) {
                    continue;
                }

                $listenerEvents[$listener->name.'@'.$method->name] =
                                Reflector::getParameterClassName($method->getParameters()[0]);
            }
        }

        return array_filter($listenerEvents);
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $basePath
     * @return string
     */
    protected static function classFromFile(SplFileInfo $file, $basePath)
    {
        $psr4Mapping = self::readComposerJson('autoload.psr-4');
        $psr4Mapping[DIRECTORY_SEPARATOR] = '/';

        // sort the array by keys length to have proper replacement.
        $psr4Mapping = self::sortByKeyLength($psr4Mapping);

        $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);
        $class = Str::replaceLast('.php', '', $class);

        return str_replace(
            array_values($psr4Mapping),
            array_keys($psr4Mapping),
            str_replace(DIRECTORY_SEPARATOR, '/', $class)
        );
    }

    /**
     * Adds a forward slash to the end of the path, if missing.
     *
     * @param  array  $psr4Mapping
     *
     * @return array
     */
    private static function normalizePaths($psr4Mapping)
    {
        foreach ($psr4Mapping as $namespace => $path) {
            if (! Str::endsWith($path, ['/'])) {
                $psr4Mapping[$namespace] .= '/';
            }
        }

        return $psr4Mapping;
    }

    /**
     * Reads a key value from composer.json file.
     *
     * @param  string  $key
     *
     * @return array
     */
    protected static function readComposerJson($key)
    {
        $composerData = json_decode(file_get_contents(app()->basePath('composer.json')), true);

        $psr4Mapping = (array) data_get($composerData, $key, []);
        $psr4Mapping = self::normalizePaths($psr4Mapping);

        return $psr4Mapping;
    }

    /**
     * Sorts an associative array by keys length.
     *
     * @param  array  $keyValueArray
     *
     * @return array
     */
    protected static function sortByKeyLength($keyValueArray)
    {
        uksort($keyValueArray, function ($key1, $key2) {
            return strlen($key2) <=> strlen($key1);
        });

        return $keyValueArray;
    }
}
