<?php

namespace Illuminate\Auth\Access;

trait HandlesAuthorization
{
    /**
     * Create a new access response.
     *
     * @param  string|null  $message
     * @param  mixed        $code
     * @return \Illuminate\Auth\Access\Response
     */
    protected function allow($message = null, $code = null)
    {
        return Response::allow($message, $code);
    }

    /**
     * Throws an unauthorized exception.
     *
     * @param  string  $message
     * @param  mixed|null  $code
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function deny($message = 'This action is unauthorized.', $code = null)
    {
        throw new AuthorizationException($message, $code);
    }
}
