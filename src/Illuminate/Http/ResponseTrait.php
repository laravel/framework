<?php

namespace Illuminate\Http;

use Exception;
use Symfony\Component\HttpFoundation\HeaderBag;
use Illuminate\Http\Exceptions\HttpResponseException;

trait ResponseTrait
{
    /**
     * The original content of the response.
     *
     * @var mixed
     */
    public $original;

    /**
     * The exception that triggered the error response (if applicable).
     *
     * @var \Exception|null
     */
    public $exception;

    /**
     * Get the status code for the response.
     *
     * @return int
     */
    public function status()
    {
        return $this->getStatusCode();
    }

    /**
     * Get the content of the response.
     *
     * @return string
     */
    public function content()
    {
        return $this->getContent();
    }

    /**
     * Get the original response content.
     *
     * @return mixed
     */
    public function getOriginalContent()
    {
        $original = $this->original;

        return $original instanceof self ? $original->{__FUNCTION__}() : $original;
    }

    /**
     * Set a header on the Response.
     *
     * @param  string  $key
     * @param  array|string  $values
     * @param  bool    $replace
     * @return $this
     */
    public function header($key, $values, $replace = true)
    {
        $this->headers->set($key, $values, $replace);

        return $this;
    }

    /**
     * Add an array of headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\HeaderBag|array  $headers
     * @return $this
     */
    public function withHeaders($headers)
    {
        if ($headers instanceof HeaderBag) {
            $headers = $headers->all();
        }

        foreach ($headers as $key => $value) {
            $this->headers->set($key, $value);
        }

        return $this;
    }

    /**
     * Add a cookie to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie|mixed  $cookie
     * @return $this
     */
    public function cookie($cookie)
    {
        return call_user_func_array([$this, 'withCookie'], func_get_args());
    }

    /**
     * Add a cookie to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie|mixed  $cookie
     * @return $this
     */
    public function withCookie($cookie)
    {
        if (is_string($cookie) && function_exists('cookie')) {
            $cookie = call_user_func_array('cookie', func_get_args());
        }

        $this->headers->setCookie($cookie);

        return $this;
    }

    /**
     * Set the exception to attach to the response.
     *
     * @param  \Exception  $e
     * @return $this
     */
    public function withException(Exception $e)
    {
        $this->exception = $e;

        // For security, we only want to expose this header in test environments
        if (app()->runningUnitTests() === true || config('app.debug') === true) {
            $this->setExceptionHeader($e);
        }

        return $this;
    }

    /**
     * Expose an exception as a header on the response.
     *
     * @param \Exception $e
     * @return $this
     */
    public function setExceptionHeader(Exception $e)
    {
        $msg = $e->getMessage();
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $msg = $e->getMessage().' : '.$e->validator->errors()->toJson();
        }

        // Strip newlines from exception messages
        $msg = preg_replace('/[\r\n]/', ' ', $msg);

        $headers = [
            'x-laravel-exception' => get_class($e),
            'x-laravel-exception-msg' => substr($msg, 0, 1024),
            'x-laravel-exception-line' => $e->getFile().':'.$e->getLine(),
        ];

        // Add these headers to the response
        $this->withHeaders($headers);

        return $this;
    }

    /**
     * Throws the response in a HttpResponseException instance.
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function throwResponse()
    {
        throw new HttpResponseException($this);
    }
}
