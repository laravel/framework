<?php

namespace Illuminate\Foundation\Testing\Concerns;

use PHPUnit_Framework_Assert as PHPUnit;

trait InteractsWithSession
{
    /**
     * Set the session to the given array.
     *
     * @param  array  $data
     * @return $this
     */
    public function withSession(array $data)
    {
        $this->session($data);

        return $this;
    }

    /**
     * Set the session to the given array.
     *
     * @param  array  $data
     * @return $this
     */
    public function session(array $data)
    {
        $this->startSession();

        foreach ($data as $key => $value) {
            $this->app['session']->put($key, $value);
        }

        return $this;
    }

    /**
     * Start the session for the application.
     *
     * @return $this
     */
    protected function startSession()
    {
        if (! $this->app['session']->isStarted()) {
            $this->app['session']->start();
        }

        return $this;
    }

    /**
     * Flush all of the current session data.
     *
     * @return $this
     */
    public function flushSession()
    {
        $this->startSession();

        $this->app['session']->flush();

        return $this;
    }

    /**
     * Assert that the session has a given value.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function seeInSession($key, $value = null)
    {
        $this->assertSessionHas($key, $value);

        return $this;
    }

    /**
     * Assert that the session has a given value.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function assertSessionHas($key, $value = null)
    {
        if (is_array($key)) {
            return $this->assertSessionHasAll($key);
        }

        if (is_null($value)) {
            PHPUnit::assertTrue($this->app['session.store']->has($key), "Session missing key: $key");
        } else {
            PHPUnit::assertEquals($value, $this->app['session.store']->get($key));
        }

        return $this;
    }

    /**
     * Assert that the session has a given list of values.
     *
     * @param  array  $bindings
     * @return $this
     */
    public function assertSessionHasAll(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertSessionHas($value);
            } else {
                $this->assertSessionHas($key, $value);
            }
        }

        return $this;
    }

    /**
     * Assert that the session does not have a given key.
     *
     * @param  string|array  $key
     * @return $this
     */
    public function assertSessionMissing($key)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                $this->assertSessionMissing($k);
            }
        } else {
            PHPUnit::assertFalse($this->app['session.store']->has($key), "Session has unexpected key: $key");
        }

        return $this;
    }

    /**
     * Assert that the session has errors bound.
     *
     * @param  string|array  $bindings
     * @param  mixed  $format
     * @return $this
     */
    public function assertSessionHasErrors($bindings = [], $format = null)
    {
        $this->assertSessionHas('errors');

        $bindings = (array) $bindings;

        $errors = $this->app['session.store']->get('errors');

        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                PHPUnit::assertTrue($errors->has($value), "Session missing error: $value");
            } else {
                PHPUnit::assertContains($value, $errors->get($key, $format));
            }
        }

        return $this;
    }

    /**
     * Assert that the session has old input.
     *
     * @return $this
     */
    public function assertHasOldInput()
    {
        $this->assertSessionHas('_old_input');

        return $this;
    }
}
