<?php

namespace Illuminate\Support;

abstract class Action
{
    public static function dispatch(mixed ...$params): mixed
    {
        $action = static::make(...$params);

        if ($action->authorize()) {
            return $action->handle();
        }

        return null;
    }

    public static function make(mixed ...$params): mixed
    {
        return new static(...$params);
    }

    abstract public function handle(): mixed;

    public function authorize(): bool
    {
        return true;
    }
}
