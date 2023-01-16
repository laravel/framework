<?php

namespace Illuminate\Testing\Fluent\Concerns;

trait Debugging
{
    /**
     * Dumps the given props.
     *
     * @param  ?string  $prop
     * @return $this
     */
    public function dump(string $prop = null): self
    {
        dump($this->prop($prop));

        return $this;
    }

    /**
     * Dumps the given props and exits.
     *
     * @param  ?string  $prop
     * @return never
     */
    public function dd(string $prop = null): void
    {
        dd($this->prop($prop));
    }

    /**
     * Retrieve a prop within the current scope using "dot" notation.
     *
     * @param  ?string  $key
     * @return mixed
     */
    abstract protected function prop(string $key = null);
}
