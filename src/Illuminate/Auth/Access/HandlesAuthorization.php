<?php

namespace Illuminate\Auth\Access;

trait HandlesAuthorization
{
    /**
     * Create a new access response.
     *
     * @param  string|null  $message
     * @param  mixed  $code
     * @return \Illuminate\Auth\Access\Response
     */
    protected function allow($message = null, $code = null)
    {
        return Response::allow($message, $code);
    }

    /**
     * Throws an unauthorized exception.
     *
     * @param  string|null  $message
     * @param  mixed|null  $code
     * @return \Illuminate\Auth\Access\Response
     */
    protected function deny($message = null, $code = null)
    {
        return Response::deny($message, $code);
    }

    /**
     * Conditionally create an access response or throw
     * an unauthorized exception.
     *
     * @param  mixed $condition
     * @param  string|null  $message
     * @param  mixed|null  $code
     * @return \Illuminate\Auth\Access\Response
     */
    protected function allowIf($condition, $message = null, $code = null)
    {
        return value($condition) ? $this->allow() : $this->deny($message, $code);
    }

    /**
     * Conditionally throw an unauthorized exception
     * or create an access response.
     *
     * @param  mixed $condition
     * @param  string|null  $message
     * @param  mixed|null  $code
     * @return \Illuminate\Auth\Access\Response
     */
    protected function denyIf($condition, $message = null, $code = null)
    {
        return value($condition) ? $this->deny($message, $code) : $this->allow();
    }
}
