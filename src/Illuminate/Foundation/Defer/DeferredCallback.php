<?php

namespace Illuminate\Foundation\Defer;

class DeferredCallback
{
    /**
     * Indicates if the deferred callback should run even on unsuccessful requests and jobs.
     */
    public bool $always = false;

    /**
     * Create a new deferred callback instance.
     *
     * @param  callable  $callback
     * @return void
     */
    public function __construct(protected $callback)
    {
    }

    /**
     * Indicate that the deferred callback should run even on unsuccessful requests and jobs.
     *
     * @param  bool  $always
     * @return $this
     */
    public function always(bool $always = true): self
    {
        $this->always = $always;

        return $this;
    }

    /**
     * Invoke the deferred callback.
     *
     * @return void
     */
    public function __invoke(): void
    {
        call_user_func($this->callback);
    }
}
