<?php

namespace Illuminate\Foundation\Events;

use Illuminate\Foundation\Support\DiscoversClasses;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

class DiscoverEvents extends DiscoversClasses
{
    /**
     * Get all of the events and listeners by searching the given listener directory.
     *
     * @param  string  $path
     * @return array
     */
    public static function within($path)
    {
        $listeners = collect(static::getListenerEvents(
            (new Finder)->files()->in($path)
        ));

        $discoveredEvents = [];

        foreach ($listeners as $listener => $events) {
            foreach ($events as $event) {
                if (! isset($discoveredEvents[$event])) {
                    $discoveredEvents[$event] = [];
                }

                $discoveredEvents[$event][] = $listener;
            }
        }

        return $discoveredEvents;
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $listeners
     * @return array
     */
    protected static function getListenerEvents($listeners)
    {
        $listenerEvents = [];

        foreach (self::discoverClasses($listeners) as $listener) {
            foreach ($listener->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ((! Str::is('handle*', $method->name) && ! Str::is('__invoke', $method->name)) ||
                    ! isset($method->getParameters()[0])) {
                    continue;
                }

                $listenerEvents[$listener->name.'@'.$method->name] =
                                Reflector::getParameterClassNames($method->getParameters()[0]);
            }
        }

        return array_filter($listenerEvents);
    }
}
