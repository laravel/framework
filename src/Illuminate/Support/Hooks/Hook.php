<?php

namespace Illuminate\Support\Hooks;

use Closure;

class Hook
{
    public function __construct(
        public string $name,
        public Closure $callback,
        public bool $isStatic = true,
        public int $weight = 100
    ) { }

    public function run(array $arguments)
    {
        return call_user_func_array($this->callback, $arguments);
    }
}
