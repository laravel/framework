<?php

namespace Illuminate\Testing\Fluent\Concerns;

trait Debugging
{
    public function dump(string $prop = null): self
    {
        dump($this->prop($prop));

        return $this;
    }

    public function dd(string $prop = null): void
    {
        dd($this->prop($prop));
    }

    abstract protected function prop(string $key = null);
}
