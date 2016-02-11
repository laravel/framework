<?php

namespace Illuminate\Foundation\Testing\Concerns;

trait InteractsWithAuthentication
{
    /**
     * Return true if the user is authenticated, false otherwise.
     *
     * @param  string|null  $guard
     * @return bool
     */
    protected function isAuthenticated($guard = null)
    {
        return $this->app->make('auth')->guard($guard)->check();
    }

    /**
     * Assert that the user is authenticated.
     *
     * @param string|null  $guard
     * @return $this
     */
    public function seeIsAuthenticated($guard = null)
    {
        $this->assertTrue($this->isAuthenticated($guard), 'The user is not authenticated');

        return $this;
    }

    /**
     * Assert that the user is not authenticated.
     *
     * @param  string|null  $guard
     * @return $this
     */
    public function dontSeeIsAuthenticated($guard = null)
    {
        $this->assertFalse($this->isAuthenticated($guard), 'The user is authenticated');

        return $this;
    }

    /**
     * Assert that the user is authenticated as the given user.
     *
     * @param  $user
     * @param  string|null  $guard
     * @return $this
     */
    public function seeIsAuthenticatedAs($user, $guard = null)
    {
        $this->assertSame(
            $this->app->make('auth')->guard($guard)->user(), $user,
            'The logged in user is not the same'
        );

        return $this;
    }
}
