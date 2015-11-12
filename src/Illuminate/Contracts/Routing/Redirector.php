<?php

namespace Illuminate\Contracts\Routing;

interface Redirector
{
    /**
     * Create a new redirect response to the "home" route.
     *
     * @param  int $status
     * @return \Illuminate\Http\RedirectResponse
     */
    public function home($status = 302);

    /**
     * Create a new redirect response to the previous location.
     *
     * @param  int $status
     * @param  array $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function back($status = 302, $headers = []);

    /**
     * Create a new redirect response to the current URI.
     *
     * @param  int $status
     * @param  array $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refresh($status = 302, $headers = []);

    /**
     * Create a new redirect response, while putting the current URL in the session.
     *
     * @param  string $path
     * @param  int $status
     * @param  array $headers
     * @param  bool $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function guest($path, $status = 302, $headers = [], $secure = null);

    /**
     * Create a new redirect response to the previously intended location.
     *
     * @param  string $default
     * @param  int $status
     * @param  array $headers
     * @param  bool $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function intended($default = '/', $status = 302, $headers = [], $secure = null);

    /**
     * Create a new redirect response to the given path.
     *
     * @param  string $path
     * @param  int $status
     * @param  array $headers
     * @param  bool $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function to($path, $status = 302, $headers = [], $secure = null);

    /**
     * Create a new redirect response to an external URL (no validation).
     *
     * @param  string $path
     * @param  int $status
     * @param  array $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function away($path, $status = 302, $headers = []);

    /**
     * Create a new redirect response to the given HTTPS path.
     *
     * @param  string $path
     * @param  int $status
     * @param  array $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function secure($path, $status = 302, $headers = []);

    /**
     * Create a new redirect response to a named route.
     *
     * @param  string $route
     * @param  array $parameters
     * @param  int $status
     * @param  array $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function route($route, $parameters = [], $status = 302, $headers = []);

    /**
     * Create a new redirect response to a controller action.
     *
     * @param  string $action
     * @param  array $parameters
     * @param  int $status
     * @param  array $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function action($action, $parameters = [], $status = 302, $headers = []);

    /**
     * Get the URL generator instance.
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator
     */
    public function getUrlGenerator();
}
