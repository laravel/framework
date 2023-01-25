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
     * Determine if the response code was "accepted".
     *
     * @return bool
     */
    public function accepted()
    {
        return $this->status() === 202;
    }

    /**
     * Determine if the response code was "no content".
     *
     * @param  int  $status
     * @return bool
     */
    public function noContent($status = 204)
    {
        return $this->status() === $status && $this->body() === '';
    }

    /**
     * Determine if the response code was "moved permanently".
     *
     * @return bool
     */
    public function movedPermanently()
    {
        return $this->status() === 301;
    }

    /**
     * Determine if the response code was "found".
     *
     * @return bool
     */
    public function found()
    {
        return $this->status() === 302;
    }

    /**
     * Determine if the response was a "bad request".
     *
     * @return bool
     */
    public function badRequest()
    {
        return $this->status() === 400;
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
     * Determine if the response was a "payment required".
     *
     * @return bool
     */
    public function paymentRequired()
    {
        return $this->status() === 402;
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

    /**
     * Determine if the response was a "Request Timeout".
     *
     * @return bool
     */
    public function requestTimeout()
    {
        return $this->status() === 408;
    }
}
