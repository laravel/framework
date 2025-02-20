<?php

namespace Illuminate\Contracts\Routing;

interface EventStreamable
{
    /**
     * Get the name for event streams.
     *
     * @return string
     */
    public function streamVia();
}
