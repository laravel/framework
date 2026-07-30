<?php

namespace Illuminate\Contracts\Auth;

interface Factory
{
    /**
     * Get a guard instance by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    public function guard($name = null);

    /**
     * Set the default guard the factory should serve.
     *
     * @param  \UnitEnum|string|null  $name
     * @return void
     */
    public function shouldUse($name);
}
