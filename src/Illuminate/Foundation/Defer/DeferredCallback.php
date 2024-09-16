<?php

namespace Illuminate\Foundation\Defer;

use Illuminate\Support\Str;

class DeferredCallback
{
    /**
     * Define whether the defer callback has been cancelled.
     *
     * @var bool
     */
    protected bool $cancelled = false;

    /**
     * Cancels a deferred callback.
     *
     * @return $this
     */
    public function cancel(): self
    {
        $this->cancelled = true;

        return $this;
    }

    /**
     * Indicate whether the deferred callback has been cancelled or not.
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    /**
     * Create a new deferred callback instance.
     *
     * @param  callable  $callback
     * @return void
     */
    public function __construct(public $callback, public ?string $name = null, public bool $always = false)
    {
        $this->name = $name ?? (string) Str::uuid();
    }

    /**
     * Specify the name of the deferred callback so it can be cancelled later.
     *
     * @param  string  $name
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
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
        if (! $this->cancelled) {
            call_user_func($this->callback);
        }
    }
}
