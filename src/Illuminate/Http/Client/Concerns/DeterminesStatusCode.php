<?php

namespace Illuminate\Http\Client\Concerns;

trait DeterminesStatusCode
{
    /**
     * Determine if the response code was "OK".
     *
     * @return bool
     */
    public function ok()
    {
        return $this->status() === 200;
    }

    /**
     * Determine if the response code was "created".
     *
     * @return bool
     */
    public function created()
    {
        return $this->status() === 201;
    }

    /**
     * Determine if the response was a 401 "Unauthorized" response.
     *
     * @return bool
     */
    public function unauthorized()
    {
        return $this->status() === 401;
    }

    /**
     * Determine if the response was a 403 "Forbidden" response.
     *
     * @return bool
     */
    public function forbidden()
    {
        return $this->status() === 403;
    }

    /**
     * Determine if the response was a 404 "Not Found" response.
     *
     * @return bool
     */
    public function notFound()
    {
        return $this->status() === 404;
    }
}
