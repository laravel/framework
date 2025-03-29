<?php

namespace Illuminate\Foundation\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class VerifyCsrfTokenAdvanced extends VerifyCsrfToken
{
    use InteractsWithTime;

    /**
     * Whether to use Double Submit Cookie.
     *
     * @var bool
     */
    protected $useDoubleSubmitCookie = false;

    /**
     * Token expiration time in minutes.
     *
     * @var int
     */
    protected $tokenExpiration = 120;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(Application $app, Encrypter $encrypter)
    {
        parent::__construct($app, $encrypter);

        if (config('security.csrf.double_submit_cookie')) {
            $this->useDoubleSubmitCookie = true;
        }

        if (config('security.csrf.expiration')) {
            $this->tokenExpiration = config('security.csrf.expiration');
        }

        if (! empty(config('security.csrf.except'))) {
            $this->except = array_merge($this->except, config('security.csrf.except'));
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        if (
            $this->isReading($request) ||
            $this->runningUnitTests() ||
            $this->inExceptArray($request) ||
            $this->tokensMatchAdvanced($request)
        ) {
            return tap($next($request), function ($response) use ($request) {
                if ($this->shouldAddXsrfTokenCookie()) {
                    $this->addCookiesToResponse($request, $response);
                }
            });
        }

        throw new TokenMismatchException('CSRF token mismatch.');
    }

    /**
     * Determine if the token matches the stored session token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatchAdvanced($request)
    {
        $token = $this->getTokenFromRequest($request);

        if (! is_string($token)) {
            return false;
        }

        $sessionToken = $request->session()->token();

        if (! is_string($sessionToken)) {
            return false;
        }

        // Check for Double Submit Cookie
        if ($this->useDoubleSubmitCookie) {
            $cookieToken = $request->cookie('csrf_token');

            if (! is_string($cookieToken) || ! hash_equals($token, $cookieToken)) {
                return false;
            }
        }

        // Check token expiration
        if (Str::contains($token, '|')) {
            list($tokenValue, $expirationTimestamp) = explode('|', $token, 2);

            if (! is_numeric($expirationTimestamp)) {
                return false;
            }

            $expirationTime = Carbon::createFromTimestamp((int) $expirationTimestamp);

            if ($expirationTime->isPast()) {
                return false;
            }

            return hash_equals($sessionToken, $tokenValue);
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Add the CSRF cookies to the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    protected function addCookiesToResponse($request, $response)
    {
        $config = config('session');
        $sameSite = config('security.csrf.same_site', 'lax');
        $secure = config('security.csrf.secure', false);

        // Add the standard XSRF-TOKEN cookie
        $response->headers->setCookie(
            new Cookie(
                'XSRF-TOKEN',
                $request->session()->token() . '|' . $this->availableAt($this->tokenExpiration * 60),
                $this->availableAt(60 * $config['lifetime']),
                $config['path'],
                $config['domain'],
                $secure,
                false,
                false,
                $sameSite
            )
        );

        // Add the plain cookie for Double Submit Cookie mechanism
        if ($this->useDoubleSubmitCookie) {
            $response->headers->setCookie(
                new Cookie(
                    'csrf_token',
                    $request->session()->token() . '|' . $this->availableAt($this->tokenExpiration * 60),
                    $this->availableAt(60 * $config['lifetime']),
                    $config['path'],
                    $config['domain'],
                    $secure,
                    false,
                    false,
                    $sameSite
                )
            );
        }
    }
} 