<?php

namespace Illuminate\Support\Traits;

trait CastsToReadonlyArray
{
    public function toReadonlyArray(): array
    {
        return \get_object_vars($this);
    }
}
