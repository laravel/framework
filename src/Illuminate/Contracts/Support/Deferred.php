<?php

namespace Illuminate\Contracts\Support;

interface Deferred
{
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides();
}
