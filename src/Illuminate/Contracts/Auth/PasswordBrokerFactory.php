<?php

namespace Illuminate\Contracts\Auth;

interface PasswordBrokerFactory
{
    /**
     * Get a password broker instance by name.
     *
     * @param  ?string  $name
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker($name = null);
}
