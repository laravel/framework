<?php

namespace Illuminate\Foundation\Configuration;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Arr;

class Middleware
{
    /**
     * The user defined global middleware stack.
     *
     * @var array
     */
    protected $global = [];

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
     * The middleware that should be replaced in the global middleware stack.
     *
     * @var array
     */
    protected $replacements = [];

    /**
     * The user defined middleware groups.
     *
     * @var array
     */
    protected $groups = [];

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
     * The middleware that should be replaced in the specified groups.
     *
     * @var array
     */
    protected $groupReplacements = [];

    /**
     * The Folio / page middleware for the application.
     *
     * @var array
     */
    protected $pageMiddleware = [];

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
    protected $statefulApi = false;

    /**
     * Indicates the API middleware group's rate limiter.
     *
     * @var string
     */
    protected $apiLimiter;

    /**
     * Indicates if Redis throttling should be applied.
     *
     * @var bool
     */
    protected $throttleWithRedis = false;

    /**
     * Indicates if sessions should be authenticated for the "web" middleware group.
     *
     * @var bool
     */
    protected $authenticatedSessions = false;

    /**
     * The default middleware aliases.
     *
     * @var array
     */
    protected $aliases = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'subscribed' => \Spark\Http\Middleware\VerifyBillableIsSubscribed::class,
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
     * Specify a middleware that should be replaced with another middleware.
     *
     * @param  string  $search
     * @param  string  $replace
     * @return $this
     */
    public function replace(string $search, string $replace)
    {
        $this->replacements[$search] = $replace;

        return $this;
    }

    /**
     * Define the global middleware for the application.
     *
     * @param  array  $middleware
     * @return $this
     */
    public function use(array $middleware)
    {
        $this->global = $middleware;

        return $this;
    }

    /**
     * Define a middleware group.
     *
     * @param  string  $group
     * @param  array  $middleware
     * @return $this
     */
    public function group(string $group, array $middleware)
    {
        $this->groups[$group] = $middleware;

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
     * Replace the given middleware in the specified group with another middleware.
     *
     * @param  string  $group
     * @param  string  $search
     * @param  string  $replace
     * @return $this
     */
    public function replaceInGroup(string $group, string $search, string $replace)
    {
        $this->groupReplacements[$group][$search] = $replace;

        return $this;
    }

    /**
     * Modify the middleware in the "web" group.
     *
     * @param  string  $group
     * @param  array|string  $append
     * @param  array|string  $prepend
     * @param  array|string  $remove
     * @param  array  $replace
     * @return $this
     */
    public function web(array|string $append = [], array|string $prepend = [], array|string $remove = [], array $replace = [])
    {
        return $this->modifyGroup('web', $append, $prepend, $remove, $replace);
    }

    /**
     * Modify the middleware in the "api" group.
     *
     * @param  string  $group
     * @param  array|string  $append
     * @param  array|string  $prepend
     * @param  array|string  $remove
     * @param  array  $replace
     * @return $this
     */
    public function api(array|string $append = [], array|string $prepend = [], array|string $remove = [], array $replace = [])
    {
        return $this->modifyGroup('api', $append, $prepend, $remove, $replace);
    }

    /**
     * Modify the middleware in the given group.
     *
     * @param  string  $group
     * @param  array|string  $append
     * @param  array|string  $prepend
     * @param  array|string  $remove
     * @param  array  $replace
     * @return $this
     */
    protected function modifyGroup(string $group, array|string $append, array|string $prepend, array|string $remove, array $replace)
    {
        if (! empty($append)) {
            $this->appendToGroup($group, $append);
        }

        if (! empty($prepend)) {
            $this->prependToGroup($group, $prepend);
        }

        if (! empty($remove)) {
            $this->removeFromGroup($group, $remove);
        }

        if (! empty($replace)) {
            foreach ($replace as $search => $replace) {
                $this->replaceInGroup($group, $search, $replace);
            }
        }

        return $this;
    }

    /**
     * Register the Folio / page middleware for the application.
     *
     * @param  array  $middleware
     * @return $this
     */
    public function pages(array $middleware)
    {
        $this->pageMiddleware = $middleware;

        return $this;
    }

    /**
     * Register additional middleware aliases.
     *
     * @param  array  $aliases
     * @return $this
     */
    public function alias(array $aliases)
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
        $middleware = $this->global ?: array_values(array_filter([
            $this->trustHosts ? \Illuminate\Http\Middleware\TrustHosts::class : null,
            \Illuminate\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]));

        $middleware = array_map(function ($middleware) {
            return isset($this->replacements[$middleware])
                ? $this->replacements[$middleware]
                : $middleware;
        }, $middleware);

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
            'web' => array_values(array_filter([
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                $this->authenticatedSessions ? 'auth.session' : null,
            ])),

            'api' => array_values(array_filter([
                $this->statefulApi ? \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class : null,
                $this->apiLimiter ? 'throttle:'.$this->apiLimiter : null,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
            ])),
        ];

        $middleware = array_merge($middleware, $this->groups);

        foreach ($middleware as $group => $groupedMiddleware) {
            foreach ($groupedMiddleware as $index => $groupMiddleware) {
                if (isset($this->groupReplacements[$group][$groupMiddleware])) {
                    $middleware[$group][$index] = $this->groupReplacements[$group][$groupMiddleware];
                }
            }
        }

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
     * Configure where authenticated users are redirected after authentication.
     *
     * @param  callable  $redirectTo
     * @return $this
     */
    public function redirectUsersUsing(callable $redirectTo)
    {
        return $this->auth(redirectTo: $redirectTo);
    }

    /**
     * Configure where guests are redirected if not authenticated.
     *
     * @param  callable  $redirectTo
     * @return $this
     */
    public function redirectGuestsUsing(callable $redirectTo)
    {
        return $this->guest(redirectTo: $redirectTo);
    }

    /**
     * Configure the behavior of the authentication middleware.
     *
     * @param  callable  $redirectTo
     * @return $this
     */
    protected function auth(callable $redirectTo)
    {
        Authenticate::redirectUsing($redirectTo);
        AuthenticateSession::redirectUsing($redirectTo);
        AuthenticationException::redirectUsing($redirectTo);

        return $this;
    }

    /**
     * Configure the behavior of the "guest" middleware.
     *
     * @param  callable  $redirectTo
     * @return $this
     */
    protected function guest(callable $redirectTo)
    {
        RedirectIfAuthenticated::redirectUsing($redirectTo);

        return $this;
    }

    /**
     * Configure the CSRF token validation middleware.
     *
     * @param  array  $except
     * @return $this
     */
    public function validateCsrfTokens(array $except = [])
    {
        ValidateCsrfToken::except($except);

        return $this;
    }

    /**
     * Configure the URL signature validation middleware.
     *
     * @param  array  $except
     * @return $this
     */
    public function validateSignatures(array $except = [])
    {
        ValidateSignature::except($except);

        return $this;
    }

    /**
     * Configure the string trimming middleware.
     *
     * @param  array  $except
     * @return $this
     */
    public function trimStrings(array $except = [])
    {
        TrimStrings::except($except);

        return $this;
    }

    /**
     * Indicate that the trusted host middleware should be enabled.
     *
     * @return $this
     */
    public function trustHosts()
    {
        $this->trustHosts = true;

        return $this;
    }

    /**
     * Indicate that Sanctum's frontend state middleware should be enabled.
     *
     * @return $this
     */
    public function statefulApi()
    {
        $this->statefulApi = true;

        return $this;
    }

    /**
     * Indicate that the API middleware group's throttling middleware should be enabled.
     *
     * @param  string  $limiter
     * @param  bool  $redis
     * @return $this
     */
    public function throttleApi($limiter = 'api', $redis = false)
    {
        $this->apiLimiter = $limiter;

        if ($redis) {
            $this->throttleWithRedis();
        }

        return $this;
    }

    /**
     * Indicate that Laravel's throttling middleware should use Redis.
     *
     * @return $this
     */
    public function throttleWithRedis()
    {
        $this->throttleWithRedis = true;

        return $this;
    }

    /**
     * Indicate that sessions should be authenticated for the "web" middleware group.
     *
     * @return $this
     */
    public function authenticateSessions()
    {
        $this->authenticatedSessions = true;

        return $this;
    }

    /**
     * Get the Folio / page middleware for the application.
     *
     * @return array
     */
    public function getPageMiddleware()
    {
        return $this->pageMiddleware;
    }

    /**
     * Get the middleware aliases.
     *
     * @return array
     */
    public function getMiddlewareAliases()
    {
        return array_merge($this->defaultAliases(), $this->customAliases);
    }

    /**
     * Get the default middleware aliases.
     *
     * @return array
     */
    protected function defaultAliases()
    {
        return [
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'subscribed' => \Spark\Http\Middleware\VerifyBillableIsSubscribed::class,
            'throttle' => $this->throttleWithRedis
                ? \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class
                : \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ];
    }
}
