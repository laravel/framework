<?php

namespace Illuminate\Support\Hooks;

use Closure;

class Hook
{
    public const PRIORITY_HIGH = 100;
    public const PRIORITY_NORMAL = 200;
    public const PRIORITY_LOW = 300;

    public static function highPriority(string $name, Closure $callback): Hook
    {
        return new static($name, $callback, self::PRIORITY_HIGH);
    }

    public static function make(string $name, Closure $callback): Hook
    {
        return new static($name, $callback, self::PRIORITY_NORMAL);
    }

    public static function lowPriority(string $name, Closure $callback): Hook
    {
        return new static($name, $callback, self::PRIORITY_LOW);
    }

    public function __construct(
        public string $name,
        public Closure $callback,
        public int $priority = self::PRIORITY_NORMAL
    ) { }

    public function run($instance = null, array $arguments = [])
    {
        return $instance
            ? $this->callback->call($instance, ...$arguments)
            : call_user_func_array($this->callback, $arguments);
    }
}
