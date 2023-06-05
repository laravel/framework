<?php

namespace Illuminate\Foundation\Configuration;

use Illuminate\Support\Arr;

class Middleware
{
    /**
     * The middleware that should be prepended to the global middleware stack.
     *
     * @var array
     */
    protected $prepends = [];

    /**
     * The middleware that should be appended to the global middleware stack.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The middleware that should be removed from the global middleware stack.
     *
     * @var array
     */
    protected $removals = [];

    /**
     * The middleware that should be prepended to the specified groups.
     *
     * @var array
     */
    protected $groupPrepends = [];

    /**
     * The middleware that should be appended to the specified groups.
     *
     * @var array
     */
    protected $groupAppends = [];

    /**
     * The middleware that should be removed from the specified groups.
     *
     * @var array
     */
    protected $groupRemovals = [];

    /**
     * Indicates if the "trust hosts" middleware is enabled.
     *
     * @var bool
     */
    protected $trustHosts = false;

    /**
     * Indicates if Sanctum's frontend state middleware is enabled.
     *
     * @var bool
     */
    protected $ensureFrontendRequestsAreStateful = false;

    /**
     * Indicates the API middleware group's rate limiter.
     *
     * @var string
     */
    protected $apiLimiter;

    /**
     * The default middleware aliases.
     *
     * @var array
     */
    protected $aliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];

    /**
     * The custom middleware aliases.
     *
     * @var array
     */
    protected $customAliases = [];

    /**
     * Prepend middleware to the application's global middleware stack.
     *
     * @param  array|string  $middleware
     * @return $this
     */
    public function prepend(array|string $middleware)
    {
        $this->prepends = array_merge(
            Arr::wrap($middleware),
            $this->prepends
        );

        return $this;
    }

    /**
     * Append middleware to the application's global middleware stack.
     *
     * @param  array|string  $middleware
     * @return $this
     */
    public function append(array|string $middleware)
    {
        $this->appends = array_merge(
            $this->appends,
            Arr::wrap($middleware)
        );

        return $this;
    }

    /**
     * Remove middleware from the application's global middleware stack.
     *
     * @param  array|string  $middleware
     * @return $this
     */
    public function remove(array|string $middleware)
    {
        $this->removals = array_merge(
            $this->removals,
            Arr::wrap($middleware)
        );

        return $this;
    }

    /**
     * Prepend the given middleware to the specified group.
     *
     * @param  string  $group
     * @param  array|string  $middleware
     * @return $this
     */
    public function prependToGroup(string $group, array|string $middleware)
    {
        $this->groupPrepends[$group] = array_merge(
            Arr::wrap($middleware),
            $this->groupPrepends[$group] ?? []
        );

        return $this;
    }

    /**
     * Append the given middleware to the specified group.
     *
     * @param  string  $group
     * @param  array|string  $middleware
     * @return $this
     */
    public function appendToGroup(string $group, array|string $middleware)
    {
        $this->groupAppends[$group] = array_merge(
            Arr::wrap($middleware),
            $this->groupAppends[$group] ?? []
        );

        return $this;
    }

    /**
     * Remove the given middleware from the specified group.
     *
     * @param  string  $group
     * @param  array|string  $middleware
     * @return $this
     */
    public function removeFromGroup(string $group, array|string $middleware)
    {
        $this->groupRemovals[$group] = array_merge(
            Arr::wrap($middleware),
            $this->groupRemovals[$group] ?? []
        );

        return $this;
    }

    /**
     * Register additional middleware aliases.
     *
     * @param  array  $aliases
     * @return $this
     */
    public function withAliases(array $aliases)
    {
        $this->customAliases = $aliases;

        return $this;
    }

    /**
     * Get the global middleware.
     *
     * @return array
     */
    public function getGlobalMiddleware()
    {
        $middleware = array_values(array_filter([
            $this->trustHosts ? \Illuminate\Http\Middleware\TrustHosts::class : null,
            \Illuminate\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]));

        return array_values(array_filter(
            array_diff(
                array_unique(array_merge($this->prepends, $middleware, $this->appends)),
                $this->removals
            )
        ));
    }

    /**
     * Get the middleware groups.
     *
     * @return array
     */
    public function getMiddlewareGroups()
    {
        $middleware = [
            'web' => [
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
            ],

            'api' => array_values(array_filter([
                $this->ensureFrontendRequestsAreStateful ? \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class : null,
                $this->apiLimiter ? \Illuminate\Routing\Middleware\ThrottleRequests::class.':'.$this->apiLimiter : null,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
            ])),
        ];

        foreach ($this->groupRemovals as $group => $removals) {
            $middleware[$group] = array_values(array_filter(
                array_diff($middleware[$group] ?? [], $removals)
            ));
        }

        foreach ($this->groupPrepends as $group => $prepends) {
            $middleware[$group] = array_values(array_filter(
                array_unique(array_merge($prepends, $middleware[$group] ?? []))
            ));
        }

        foreach ($this->groupAppends as $group => $appends) {
            $middleware[$group] = array_values(array_filter(
                array_unique(array_merge($middleware[$group] ?? [], $appends))
            ));
        }

        return $middleware;
    }

    /**
     * Indicate that the trusted host middleware should be enabled.
     *
     * @return $this
     */
    public function ensureHostsAreTrusted()
    {
        $this->trustHosts = true;

        return $this;
    }

    /**
     * Indicate that Sanctum's frontend state middleware should be enabled.
     *
     * @return $this
     */
    public function ensureFrontendRequestsAreStateful()
    {
        $this->ensureFrontendRequestsAreStateful = true;

        return $this;
    }

    /**
     * Indicate that the API middleware group's throttling middleware should be enabled.
     *
     * @param  string  $limiter
     * @return $this
     */
    public function ensureApiIsThrottled($limiter = 'api')
    {
        $this->apiLimiter = $limiter;

        return $this;
    }

    /**
     * Get the middleware aliases.
     *
     * @return array
     */
    public function getMiddlewareAliases()
    {
        return array_merge($this->aliases, $this->customAliases);
    }
}
