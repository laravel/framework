<?php

namespace Illuminate\Foundation\Benchmark\Renderers\Concerns;

use Illuminate\Support\Str;
use Laravel\SerializableClosure\Support\ReflectionClosure;

trait InspectsClosures
{
    /**
     * Get the code of the given callback.
     *
     * @param  \Closure  $callback
     * @return string
     */
    protected function getCode($callback)
    {
        $code = (new ReflectionClosure($callback))->getCode();

        if (Str::startsWith($code, 'fn () => ')) {
            $code = Str::after($code, 'fn () => ');
        }

        if (Str::startsWith($code, 'function () {')) {
            $code = Str::after($code, 'function () {');
        }

        if (Str::endsWith($code, '}')) {
            $code = Str::beforeLast($code, '}');
        }

        return trim($code);
    }
}
