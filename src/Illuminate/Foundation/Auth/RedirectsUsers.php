<?php

namespace Illuminate\Foundation\Auth;

trait RedirectsUsers
{
    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
    }

    /**
     * Get the logout redirect path.
     *
     * @return string
     */
    public function logoutRedirectPath()
    {
        return property_exists($this, 'logoutRedirectTo') ? $this->logoutRedirectTo : '/';
    }
}
