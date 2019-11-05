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
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
    }

    /**
     * Get the post register / login redirect route.
     *
     * @return string
     */
    public function redirectRoute()
    {
        if (method_exists($this, 'redirectRoute')) {
            return $this->redirectRoute();
        }

        return property_exists($this, 'redirectRoute') ? $this->redirectRoute : false;
    }
}
