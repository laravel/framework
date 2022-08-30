<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Support\Arr;
use Symfony\Component\Console\SignalRegistry\SignalRegistry;

trait InteractsWithSignals
{
    /**
     * Sets a trap to be run when the given signal(s) occurs.
     *
     * @param  iterable<array-key, int>|int  $signals
     * @param  callable  $callback
     * @return void
     */
    public function trap($signals, $callback)
    {
        if ($this->areSignalsSupported()) {
            collect(Arr::wrap($signals))->each(
                fn ($signal) => $this->getApplication()
                    ->getSignalRegistry()
                    ->register($signal, $callback),
            );
        }
    }

    /**
     * Checks if signals are supported.
     *
     * @return bool
     */
    protected function areSignalsSupported()
    {
        return defined('SIGINT')
            && SignalRegistry::isSupported();
    }
}
