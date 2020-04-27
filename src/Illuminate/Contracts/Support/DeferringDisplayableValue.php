<?php

namespace Illuminate\Contracts\Support;

interface DeferringDisplayableValue
{
    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return mixed
     */
    public function resolveDisplayableValue();
}
