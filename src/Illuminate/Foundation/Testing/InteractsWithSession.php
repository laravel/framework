<?php

namespace Illuminate\Foundation\Testing;

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
     * @return void
     */
    public function session(array $data)
    {
        $this->startSession();

        foreach ($data as $key => $value) {
            $this->app['session']->put($key, $value);
        }
    }

    /**
     * Start the session for the application.
     *
     * @return void
     */
    protected function startSession()
    {
        if (! $this->app['session']->isStarted()) {
            $this->app['session']->start();
        }
    }

    /**
     * Flush all of the current session data.
     *
     * @return void
     */
    public function flushSession()
    {
        $this->startSession();

        $this->app['session']->flush();
    }
}
