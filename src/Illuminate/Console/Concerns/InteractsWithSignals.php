<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Console\Signals;
use Illuminate\Support\Arr;

trait InteractsWithSignals
{
    /**
     * The signals instance, if any.
     *
     * @var \Illuminate\Console\Signals|null
     */
    protected $signals;

    /**
     * Sets a trap to be run when the given signal(s) occurs.
     *
     * @param  iterable<array-key, int>|int  $signals
     * @param  callable  $callback
     * @return void
     */
    public function trap($signals, $callback)
    {
        Signals::whenAvailable(function () use ($signals, $callback) {
            $this->signals ??= new Signals(
                $this->getApplication()->getSignalRegistry(),
            );

            collect(Arr::wrap($signals))
                ->each(fn ($signal) => $this->signals->register($signal, $callback));
        });
    }

    /**
     * Untraps traps set by the command class.
     *
     * @return void
     *
     * @internal
     */
    public function untrap()
    {
        if (! is_null($this->signals)) {
            $this->signals->unregister();

            $this->signals = null;
        }
    }
}
