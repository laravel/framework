<?php

namespace Illuminate\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Identity\Identifiable;
use Illuminate\Contracts\Auth\Providers\BasicUserProvider;
use Illuminate\Contracts\Auth\Providers\StatefulUserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * @implements Guard<Identifiable>
 */
class RequestGuard implements Guard
{
    use GuardHelpers, Macroable;

    protected $callback;

    /**
     * The request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * The user provider implementation.
     */
    protected ?BasicUserProvider $provider;

    /**
     * Create a new authentication guard.
     *
     * @param  callable(Request):Identifiable  $callback
     * @param  Request  $request
     * @param  StatefulUserProvider|null  $provider
     * @return void
     */
    public function __construct(callable $callback, Request $request, ?BasicUserProvider $provider = null)
    {
        $this->request = $request;
        $this->callback = $callback;
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Identifiable|null
     */
    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (! is_null($this->user)) {
            return $this->user;
        }

        $user = call_user_func(
            $this->callback, $this->request, $this->getProvider()
        );

        return $this->user = $user;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(#[SensitiveParameter] array $credentials = [])
    {
        return ! is_null((new static(
            $this->callback, $credentials['request'], $this->getProvider()
        ))->user());
    }

    /**
     * Set the current user.
     *
     * @param  Identifiable  $user
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setUser(Identifiable $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the current request instance.
     *
     * @param  Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the user provider used by the guard.
     *
     * @param  BasicUserProvider  $provider
     * @return void
     */
    public function setProvider(BasicUserProvider $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * Get the user provider used by the guard.
     *
     * @return BasicUserProvider|null
     */
    public function getProvider(): ?BasicUserProvider
    {
        return $this->provider;
    }
}
