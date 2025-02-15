<?php

namespace Illuminate\Contracts\Validation;

interface StopUponFailure
{
    /**
     * Trigger the validation of the field to no longer continue.
     *
     * @return bool
     */
    public function shouldStop(): bool;
}
