<?php

namespace Illuminate\Contracts\Pipeline;

interface Hub
{
    /**
     * Send an object through one of the available pipelines.
     *
     * @param  mixed  $object
     * @param  string  $pipeline
     * @return mixed
     */
    public function pipe($object, string $pipeline = 'default');
}
