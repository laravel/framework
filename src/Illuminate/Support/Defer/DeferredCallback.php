<?php

namespace Illuminate\Support\Defer;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DeferredCallback
{
    /**
     * Create a new deferred callback instance.
     *
     * @param  callable  $callback
     * @return void
     */
    public function __construct(
        public $callback,
        public ?string $name = null,
        public bool $always = false,
        public mixed $conditional = true,
    ) {
        $this->name = $name ?? (string) Str::uuid();
    }

    /**
     * Specify an if condition to run or not the callback.
     *
     * @param  bool|callable  $conditional
     * @return $this
     */
    public function if(bool|callable $conditional): self
    {
        $this->conditional = $conditional;

        return $this;
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
     * Specify an if condition to run or not the callback.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @param  \Symfony\Component\HttpFoundation\Response|null  $response
     * @return bool
     */
    public function shouldCall(Request $request = null, Response $response = null): bool
    {
        return is_callable($this->conditional)
            ? call_user_func($this->conditional, $request, $response)
            : boolval($this->conditional);
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
