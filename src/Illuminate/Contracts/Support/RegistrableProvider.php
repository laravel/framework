<?php

namespace Illuminate\Contracts\Support;

interface RegistrableProvider
{
    /**
     * Register the services.
     *
     * @return void
     */
    public function register();
}
