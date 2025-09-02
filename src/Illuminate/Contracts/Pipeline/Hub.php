<?php

namespace Illuminate\Contracts\Pipeline;

interface Hub
{
    /**
     * Send an object through one of the available pipelines.
     *
     * @param  string|null  $pipeline
     */
    public function pipe($object, $pipeline = null);
}
