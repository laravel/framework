<?php

namespace Illuminate\Support;

use Illuminate\Support\Hooks\HookCollection;

trait Hookable
{
    protected static function runStaticHooks($name, ...$arguments)
    {
        HookCollection::for(static::class)->run($name, null, $arguments);
    }

    protected function runHooks($name, ...$arguments)
    {
        HookCollection::for(static::class)->run($name, $this, $arguments);
    }
}
