<?php

namespace Illuminate\Contracts\Support;

interface Transformable
{
    /**
     * Get data to apply transformations.
     *
     * @return array
     */
    public function getTransformableData();
}
