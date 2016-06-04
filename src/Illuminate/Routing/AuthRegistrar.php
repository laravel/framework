<?php

namespace Illuminate\Routing;

class AuthRegistrar
{
    /**
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The default actions for a auth controller.
     *
     * @var array
     */
    protected $authDefaults = ['showLoginForm', 'login', 'logout', 'showRegistrationForm', 'register', 'showResetForm', 'sendResetLinkEmail', 'reset'];

    /**
     * Create a new auth registrar instance.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register the typical authentication routes for an application.
     *
     * @param  array $options
     */
    public function register(array $options = [])
    {
        $defaults = $this->authDefaults;

        foreach ($this->getAuthMethods($defaults, $options) as $m) {
            $this->{'addAuth'.ucfirst($m)}($options);
        }
    }

    /**
     * Get the applicable auth methods.
     *
     * @param array $defaults
     * @param array $options
     *
     * @return array
     */
    protected function getAuthMethods($defaults, $options)
    {
        if (isset($options['only'])) {
            return array_intersect($defaults, (array) $options['only']);
        } elseif (isset($options['except'])) {
            return array_diff($defaults, (array) $options['except']);
        }

        return $defaults;
    }

    /**
     * Add the showLoginForm method for a auth route.
     *
     * @return Route
     */
    protected function addAuthShowLoginForm()
    {
        $uri = 'login';
        $action = ['as' => '', 'uses' => 'Auth\AuthController@showLoginForm'];

        return $this->router->get($uri, $action);
    }

    /**
     * Add the login method for a auth route.
     *
     * @return Route
     */
    protected function addAuthLogin()
    {
        $uri = 'login';
        $action = ['as' => '', 'uses' => 'Auth\AuthController@login'];

        return $this->router->post($uri, $action);
    }

    /**
     * Add the logout method for a auth route.
     *
     * @return Route
     */
    protected function addAuthLogout()
    {
        $uri = 'logout';
        $action = ['as' => '', 'uses' => 'Auth\AuthController@logout'];

        return $this->router->get($uri, $action);
    }

    /**
     * Add the showRegistrationForm method for a auth route.
     *
     * @return Route
     */
    protected function addAuthShowRegistrationForm()
    {
        $uri = 'register';
        $action = ['as' => '', 'uses' => 'Auth\AuthController@showRegistrationForm'];

        return $this->router->get($uri, $action);
    }

    /**
     * Add the register method for a auth route.
     *
     * @return Route
     */
    protected function addAuthRegister()
    {
        $uri = 'register';
        $action = ['as' => '', 'uses' => 'Auth\AuthController@register'];

        return $this->router->post($uri, $action);
    }

    /**
     * Add the showResetForm method for a auth route.
     *
     * @return Route
     */
    protected function addAuthShowResetForm()
    {
        $uri = 'password/reset/{token?}';
        $action = ['as' => '', 'uses' => 'Auth\AuthController@showResetForm'];

        return $this->router->get($uri, $action);
    }

    /**
     * Add the sendResetLinkEmail method for a auth route.
     *
     * @return Route
     */
    protected function addAuthSendResetLinkEmail()
    {
        $uri = 'password/email';
        $action = ['as' => '', 'uses' => 'Auth\AuthController@sendResetLinkEmail'];

        return $this->router->post($uri, $action);
    }

    /**
     * Add the reset method for a auth route.
     *
     * @return Route
     */
    protected function addAuthReset()
    {
        $uri = 'password/reset';
        $action = ['as' => '', 'uses' => 'Auth\AuthController@reset'];

        return $this->router->post($uri, $action);
    }

}
