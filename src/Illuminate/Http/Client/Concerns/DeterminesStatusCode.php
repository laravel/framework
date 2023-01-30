<?php

namespace Illuminate\Http\Client\Concerns;

use Illuminate\Http\Response;

trait DeterminesStatusCode
{
    /**
     * Determine if the response code was 200 "OK" response.
     *
     * @return bool
     */
    public function ok()
    {
        return $this->status() === Response::HTTP_OK;
    }

    /**
     * Determine if the response code was 201 "Created" response.
     *
     * @return bool
     */
    public function created()
    {
        return $this->status() === Response::HTTP_CREATED;
    }

    /**
     * Determine if the response code was 202 "Accepted" response.
     *
     * @return bool
     */
    public function accepted()
    {
        return $this->status() === Response::HTTP_ACCEPTED;
    }

    /**
     * Determine if the response code was the given status code and the body has no content.
     *
     * @param  int  $status
     * @return bool
     */
    public function noContent($status = Response::HTTP_NO_CONTENT)
    {
        return $this->status() === $status && $this->body() === '';
    }

    /**
     * Determine if the response code was a 301 "Moved Permanently".
     *
     * @return bool
     */
    public function movedPermanently()
    {
        return $this->status() === Response::HTTP_MOVED_PERMANENTLY;
    }

    /**
     * Determine if the response code was a 302 "Found" response.
     *
     * @return bool
     */
    public function found()
    {
        return $this->status() === Response::HTTP_FOUND;
    }

    /**
     * Determine if the response was a 400 "Bad Request" response.
     *
     * @return bool
     */
    public function badRequest()
    {
        return $this->status() === Response::HTTP_BAD_REQUEST;
    }

    /**
     * Determine if the response was a 401 "Unauthorized" response.
     *
     * @return bool
     */
    public function unauthorized()
    {
        return $this->status() === Response::HTTP_UNAUTHORIZED;
    }

    /**
     * Determine if the response was a 402 "Payment Required" response.
     *
     * @return bool
     */
    public function paymentRequired()
    {
        return $this->status() === Response::HTTP_PAYMENT_REQUIRED;
    }

    /**
     * Determine if the response was a 403 "Forbidden" response.
     *
     * @return bool
     */
    public function forbidden()
    {
        return $this->status() === Response::HTTP_FORBIDDEN;
    }

    /**
     * Determine if the response was a 404 "Not Found" response.
     *
     * @return bool
     */
    public function notFound()
    {
        return $this->status() === Response::HTTP_NOT_FOUND;
    }

    /**
     * Determine if the response was a 408 "Request Timeout" response.
     *
     * @return bool
     */
    public function requestTimeout()
    {
        return $this->status() === Response::HTTP_REQUEST_TIMEOUT;
    }

    /**
     * Determine if the response was a 409 "Conflict" response.
     *
     * @return bool
     */
    public function conflict()
    {
        return $this->status() === Response::HTTP_CONFLICT;
    }

    /**
     * Determine if the response was a 422 "Unprocessable Entity" response.
     *
     * @return bool
     */
    public function unprocessableEntity()
    {
        return $this->status() === Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    /**
     * Determine if the response was a 429 "Too Many Requests" response.
     *
     * @return bool
     */
    public function tooManyRequests()
    {
        return $this->status() === Response::HTTP_TOO_MANY_REQUESTS;
    }
}
