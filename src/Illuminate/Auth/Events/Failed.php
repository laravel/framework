<?php

namespace Illuminate\Auth\Events;

class Failed
{
    /**
     * The user the attempter was trying to authenticate as.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public $user;

    /**
     * The credentials provided by the attempter.
     *
     * @var array
     */
    public $credentials;

    /**
     * The request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $credentials
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct($user, $credentials, $request)
    {
        $this->user = $user;
        $this->credentials = $credentials;
        $this->request = $request;
    }
}
