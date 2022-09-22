<?php

namespace Illuminate\Foundation\Benchmark\Renderers\Concerns;

use Illuminate\Foundation\Benchmark\Renderers\ConsoleRenderer;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\Support\ReflectionClosure;
use function Termwind\terminal;

trait InspectsClosures
{
    /**
     * Get the code of the given callback.
     *
     * @param  \Closure  $callback
     * @return string
     */
    protected function getCodeDescription($callback)
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

        $code = str($code)->trim();

        $limit = $this instanceof ConsoleRenderer ? (terminal()->width() - 25) : 40;

        if ($code->contains("\n")) {
            $code = Str::limit($code->explode("\n")->first(), $limit, '').' …';
        } else {
            $code = Str::limit($code, $limit, '…');
        }

        return (string) $code;
    }
}
