<?php

namespace Illuminate\Support\Traits;

use DeepCopy\DeepCopy;

trait HasDeepCopy
{
    /**
     * Returns a deep copy of the object
     *
     * @return object
     */
    public function deepCopy()
    {
        $copier = new DeepCopy();
        return $copier->copy($this);
    }
}
