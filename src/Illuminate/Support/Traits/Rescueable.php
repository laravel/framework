<?php

namespace Illuminate\Support\Traits;

trait Rescueable
{
    /**
     * Catch a potential exception and return a default value or the same instance.
     *
     * @template TValue
     * @template TFallback
     *
     * @param  (callable(\Throwable): TFallback)|TFallback  $rescue
     * @param  bool|callable(\Throwable): bool  $report
     * @return $this
     */
    public function rescue($rescue = null, $report = true)
    {
        return tap($this, $callback)
    }
}
