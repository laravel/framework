<?php

namespace Illuminate\Auth\VerifyEmails;

use Illuminate\Contracts\Auth\CanVerifyEmail as CanVerifyEmailContract;

interface TokenRepositoryInterface
{
    /**
     * Create a new token.
     *
     * @param  \Illuminate\Contracts\Auth\CanVerifyEmail  $user
     * @return string
     */
    public function create(CanVerifyEmailContract $user);

    /**
     * Determine if a token record exists and is valid.
     *
     * @param  \Illuminate\Contracts\Auth\CanVerifyEmail  $user
     * @param  string  $token
     * @return bool
     */
    public function exists(CanVerifyEmailContract $user, $token);

    /**
     * Delete a token record.
     *
     * @param  string  $token
     * @return void
     */
    public function delete($token);

    /**
     * Delete expired tokens.
     *
     * @return void
     */
    public function deleteExpired();
}
