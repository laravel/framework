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
     * @param  string  $namespace
     * @return array
     */
    public static function within($listenerPath, $namespace)
    {
        return collect(static::getListenerEvents(
            (new Finder)->files()->in($listenerPath), realpath($listenerPath), $namespace
        ))->mapToDictionary(function ($event, $listener) {
            return [$event => $listener];
        })->all();
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $listeners
     * @param  string  $basePath
     * @param  string  $namespace
     * @return array
     */
    protected static function getListenerEvents($listeners, $basePath, $namespace)
    {
        $listenerEvents = [];

        foreach ($listeners as $listener) {
            try {
                $listener = new ReflectionClass(
                    static::classFromFile($listener, $basePath, $namespace)
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
     * @param  string  $namespace
     * @return string
     */
    protected static function classFromFile(SplFileInfo $file, $basePath, $namespace)
    {
        return str_replace(
            [$basePath, DIRECTORY_SEPARATOR],
            [$namespace, '\\'],
            ucfirst(Str::replaceLast('.php', '', $file->getRealPath()))
        );
    }
}
