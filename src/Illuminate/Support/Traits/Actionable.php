<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Right Reserved.
 */

namespace Illuminate\Support\Traits;

use BadMethodCallException;
use Illuminate\Container\Container;
use Illuminate\Validation\UnauthorizedException;

trait Actionable
{
    public static function dispatch(mixed ...$params): mixed
    {
        $container = Container::getInstance();

        $action = $container->make(static::class, $params);

        $authorized = false;
        if (method_exists($action, 'authorize')) {
            $authorized = $container->call([$action, 'authorize'], $params);
        }

        if (! is_bool($authorized) || ! $authorized) {
            return method_exists($action, 'unauthorized')
                ? $container->call([$action, 'unauthorized'], $params)
                : throw new UnauthorizedException('This action is unauthorized.');
        }

        if (method_exists($action, 'handle')) {
            return $container->call([$action, 'handle'], $params);
        }

        if (method_exists($action, '__invoke')) {
            return $container->call([$action, '__invoke'], $params);
        }

        throw new BadMethodCallException('Neither handle nor __invoke method exists on '.static::class);
    }

    public function authorize(): bool
    {
        return true;
    }
}
