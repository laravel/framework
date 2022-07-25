<?php

namespace Illuminate\Contracts\Broadcasting;

interface ShouldBeUnique
{
    /**
     * Unique identifier for lock
     *
     * @return mixed
     */
    public function uniqueId();
}
