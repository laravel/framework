<?php

namespace Illuminate\Support;

use Illuminate\Support\Hooks\HookCollection;

trait Hookable
{
    protected static function runHooks($name, ...$arguments)
    {
        HookCollection::for(static::class)->run($name, $arguments);
    }
}
