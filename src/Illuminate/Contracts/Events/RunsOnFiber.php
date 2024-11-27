<?php

namespace Illuminate\Contracts\Events;

use Fiber;

trait RunsOnFiber
{
    public function handleWithFiber(...$arguments)
    {
        $self = $this;
        $fiber = new Fiber(function () use ($arguments, $self) {
            return $self->handle(...$arguments);
        });
        $fiber->start();

        return $fiber->getReturn();
    }
}
