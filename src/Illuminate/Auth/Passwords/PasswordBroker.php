<?php

namespace Illuminate\Auth\Passwords;

use Closure;
use Illuminate\Support\Arr;
use UnexpectedValueException;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\PasswordBroker as PasswordBrokerContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class PasswordBroker implements PasswordBrokerContract
{
    /**
     * The user provider implementation.
     *
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    protected $users;

    /**
     * The number minutes for which the reset link should be considered valid.
     *
     * @var int
     */
    protected $expiration;

    /**
     * The custom password validator callback.
     *
     * @var \Closure
     */
    protected $passwordValidator;

    /**
     * Create a new password broker instance.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $users
     * @param  int  $expiration
     * @return void
     */
    public function __construct(UserProvider $users, $expiration = 60)
    {
        $this->users = $users;
        $this->expiration = $expiration;
    }

    /**
     * Send a password reset link to a user.
     *
     * @param  array  $credentials
     * @return string
     */
    public function sendResetLink(array $credentials)
    {
        // First we will check to see if we found a user at the given credentials and
        // if we did not we will redirect back to this current URI with a piece of
        // "flash" data in the session to indicate to the developers the errors.
        $user = $this->getUser($credentials);

        if (is_null($user)) {
            return static::INVALID_USER;
        }

        // Once we have a user instance, we are ready to send the message out to this
        // user with a link to reset their password. We will then redirect back to
        // the current URI having nothing set in the session to indicate errors.
        $user->sendPasswordResetNotification($this->expiration);

        return static::RESET_LINK_SENT;
    }

    /**
     * Reset the user's password.
     *
     * @param  array  $credentials
     * @param  \Closure  $callback
     * @return mixed
     */
    public function reset(array $credentials, Closure $callback)
    {
        // If the responses from the validate method is not a user instance, we will
        // assume that it is a redirect and simply return it from this method and
        // the user is properly redirected having an error message on the post.
        $user = $this->validateReset($credentials);

        if (! $user instanceof CanResetPasswordContract) {
            return $user;
        }

        $password = $credentials['password'];

        // Once the reset has been validated, we'll call the given callback with the
        // new password. This gives the user an opportunity to store the password
        // in their persistent storage. Then we will return out a reset result.
        $callback($user, $password);

        return static::PASSWORD_RESET;
    }

    /**
     * Validate a password reset for the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\CanResetPassword|string
     */
    protected function validateReset(array $credentials)
    {
        if (is_null($user = $this->getUser($credentials))) {
            return static::INVALID_USER;
        }

        if (! $this->validateNewPassword($credentials)) {
            return static::INVALID_PASSWORD;
        }

        if (! ($credentials['hasValidSignature'] ?? false)) {
            return static::INVALID_TOKEN;
        }

        return $user;
    }

    /**
     * Set a custom password validator.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function validator(Closure $callback)
    {
        $this->passwordValidator = $callback;
    }

    /**
     * Determine if the passwords match for the request.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validateNewPassword(array $credentials)
    {
        if (isset($this->passwordValidator)) {
            list($password, $confirm) = [
                $credentials['password'],
                $credentials['password_confirmation'],
            ];

            return call_user_func(
                $this->passwordValidator, $credentials
            ) && $password === $confirm;
        }

        return $this->validatePasswordWithDefaults($credentials);
    }

    /**
     * Determine if the passwords are valid for the request.
     *
     * @param  array  $credentials
     * @return bool
     */
    protected function validatePasswordWithDefaults(array $credentials)
    {
        list($password, $confirm) = [
            $credentials['password'],
            $credentials['password_confirmation'],
        ];

        return $password === $confirm && mb_strlen($password) >= 6;
    }

    /**
     * Get the user for the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\CanResetPassword|null
     *
     * @throws \UnexpectedValueException
     */
    public function getUser(array $credentials)
    {
        $credentials = Arr::except($credentials, ['hasValidSignature']);

        $user = $this->users->retrieveByCredentials($credentials);

        if ($user && ! $user instanceof CanResetPasswordContract) {
            throw new UnexpectedValueException('User must implement CanResetPassword interface.');
        }

        return $user;
    }
}
