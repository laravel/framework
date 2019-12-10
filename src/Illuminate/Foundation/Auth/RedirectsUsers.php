<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Support\Facades\Config;

trait RedirectsUsers
{
    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo')
            ? $this->redirectTo
            : Config::get('auth.redirect_to', '/home');
    }
}
