<?php

namespace Illuminate\Routing;

use Illuminate\Auth\AuthManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Traits\Macroable;

class Redirector
{
    use Macroable;

    /**
     * The URL generator instance.
     *
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $generator;

    /**
     * The session store instance.
     *
     * @var \Illuminate\Session\Store
     */
    protected $session;

    /**
     * The auth manager instance.
     *
     * @var \Illuminate\Auth\AuthManager|null
     */
    protected $auth;

    /**
     * Create a new Redirector instance.
     *
     * @param  \Illuminate\Routing\UrlGenerator  $generator
     * @param  \Illuminate\Auth\AuthManager|null  $auth
     */
    public function __construct(UrlGenerator $generator, ?AuthManager $auth = null)
    {
        $this->generator = $generator;
        $this->auth = $auth;
    }

    /**
     * Create a new redirect response to the previous location.
     *
     * @param  int  $status
     * @param  array  $headers
     * @param  mixed  $fallback
     * @return \Illuminate\Http\RedirectResponse
     */
    public function back($status = 302, $headers = [], $fallback = false)
    {
        return $this->createRedirect($this->generator->previous($fallback), $status, $headers);
    }

    /**
     * Create a new redirect response to the current URI.
     *
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refresh($status = 302, $headers = [])
    {
        return $this->to($this->generator->getRequest()->path(), $status, $headers);
    }

    /**
     * Create a new redirect response, while putting the current URL in the session.
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function guest($path, $status = 302, $headers = [], $secure = null)
    {
        $request = $this->generator->getRequest();

        $intended = $request->isMethod('GET') && $request->route() && ! $request->expectsJson()
            ? $this->generator->full()
            : $this->generator->previous();

        if ($intended) {
            $this->setIntendedUrl($intended);
        }

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Create a new redirect response to the previously intended location.
     *
     * @param  mixed  $default
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function intended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        $path = $this->session->pull($this->getIntendedUrlSessionKey(), $default);

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Create a new redirect response to the given path.
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function to($path, $status = 302, $headers = [], $secure = null)
    {
        return $this->createRedirect($this->generator->to($path, [], $secure), $status, $headers);
    }

    /**
     * Create a new redirect response to an external URL (no validation).
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function away($path, $status = 302, $headers = [])
    {
        return $this->createRedirect($path, $status, $headers);
    }

    /**
     * Create a new redirect response to the given HTTPS path.
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function secure($path, $status = 302, $headers = [])
    {
        return $this->to($path, $status, $headers, true);
    }

    /**
     * Create a new redirect response to a named route.
     *
     * @param  \BackedEnum|string  $route
     * @param  mixed  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function route($route, $parameters = [], $status = 302, $headers = [])
    {
        return $this->to($this->generator->route($route, $parameters), $status, $headers);
    }

    /**
     * Create a new redirect response to a signed named route.
     *
     * @param  \BackedEnum|string  $route
     * @param  mixed  $parameters
     * @param  \DateTimeInterface|\DateInterval|int|null  $expiration
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function signedRoute($route, $parameters = [], $expiration = null, $status = 302, $headers = [])
    {
        return $this->to($this->generator->signedRoute($route, $parameters, $expiration), $status, $headers);
    }

    /**
     * Create a new redirect response to a signed named route.
     *
     * @param  \BackedEnum|string  $route
     * @param  \DateTimeInterface|\DateInterval|int|null  $expiration
     * @param  mixed  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function temporarySignedRoute($route, $expiration, $parameters = [], $status = 302, $headers = [])
    {
        return $this->to($this->generator->temporarySignedRoute($route, $expiration, $parameters), $status, $headers);
    }

    /**
     * Create a new redirect response to a controller action.
     *
     * @param  string|array  $action
     * @param  mixed  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function action($action, $parameters = [], $status = 302, $headers = [])
    {
        return $this->to($this->generator->action($action, $parameters), $status, $headers);
    }

    /**
     * Create a new redirect response.
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function createRedirect($path, $status, $headers)
    {
        return tap(new RedirectResponse($path, $status, $headers), function ($redirect) {
            if (isset($this->session)) {
                $redirect->setSession($this->session);
            }

            $redirect->setRequest($this->generator->getRequest());
        });
    }

    /**
     * Get the URL generator instance.
     *
     * @return \Illuminate\Routing\UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->generator;
    }

    /**
     * Set the active session store.
     *
     * @param  \Illuminate\Session\Store  $session
     * @return void
     */
    public function setSession(SessionStore $session)
    {
        $this->session = $session;
    }

    /**
     * Get the "intended" URL from the session.
     *
     * @return string|null
     */
    public function getIntendedUrl()
    {
        return $this->session->get($this->getIntendedUrlSessionKey());
    }

    /**
     * Set the "intended" URL in the session.
     *
     * @param  string  $url
     * @return $this
     */
    public function setIntendedUrl($url)
    {
        $this->session->put($this->getIntendedUrlSessionKey(), $url);

        return $this;
    }

    /**
     * Get the "intended" URL for the given guard from the session.
     *
     * @param string|null $authDriver
     * @return string|null
     */
    public function getIntendedUrlForGuard(?string $authDriver = null): ?string
    {
        return $this->session->get($this->getIntendedUrlSessionKey($authDriver));
    }

    /**
     * Set the "intended" URL for the given guard from the session.
     *
     * @param string $url
     * @param string|null $authDriver
     * @return $this
     */
    public function setIntendedUrlForGuard(string $url, ?string $authDriver = null): self
    {
        $this->session->put($this->getIntendedUrlSessionKey($authDriver), $url);

        return $this;
    }

    /**
     * Get the session key for the "intended" URL for the current guard.
     * 
     * @param string|null $authDriver
     * @return string
     */
    protected function getIntendedUrlSessionKey(?string $authDriver = null)
    {
        $guardName = $authDriver ?? $this->auth?->getDefaultDriver() ?? 'web';

        return "$guardName.url.intended";
    }
}
