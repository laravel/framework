<?php

namespace Illuminate\Contracts\Queue;

interface HasContext
{
    /**
     * Get the context to include in the job payload.
     *
     * @return array
     */
    public function context(): array;
}
