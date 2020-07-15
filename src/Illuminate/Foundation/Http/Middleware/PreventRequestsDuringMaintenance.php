<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Exceptions\MaintenanceModeException;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\IpUtils;

class PreventRequestsDuringMaintenance
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The URIs that should be accessible while maintenance mode is enabled.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Illuminate\Foundation\Http\Exceptions\MaintenanceModeException
     */
    public function handle($request, Closure $next)
    {
        if ($this->app->isDownForMaintenance() && ! $this->hasBypassCookie($request)) {
            $data = json_decode(file_get_contents($this->app->storagePath().'/framework/down'), true);

            if (isset($data['allowed']) && IpUtils::checkIp($request->ip(), (array) $data['allowed'])) {
                return $this->prepareResponse($next($request));
            }

            if ($this->inExceptArray($request)) {
                return $this->prepareResponse($next($request));
            }

            throw new MaintenanceModeException($data['time'], $data['retry'], $data['message']);
        }

        return $this->prepareResponse($next($request));
    }

    /**
     * Determine if the incoming request has a maintenance mode bypass cookie.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function hasBypassCookie($request)
    {
        if (! $request->cookie('laravel_maintenance')) {
            return false;
        }

        $payload = json_decode(base64_decode($request->cookie('laravel_maintenance')), true);

        return is_array($payload) &&
            is_numeric($payload['expires_at'] ?? null) &&
            isset($payload['mac']) &&
            hash_hmac('SHA256', $payload['expires_at'], base64_decode(substr(config('app.key'), 7))) === $payload['mac'] &&
            (int) $payload['expires_at'] >= Carbon::now()->getTimestamp();
    }

    /**
     * Determine if the request has a URI that should be accessible in maintenance mode.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare the outgoing response, attaching any necessary cookies.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Http\Response
     */
    protected function prepareResponse($response)
    {
        if (false) {
            return $response;
        }

        $expiresAt = now()->addHours(1)->getTimestamp();

        return $response->withCookie(
            'laravel_maintenance',
            base64_encode(json_encode([
                'expires_at' => $expiresAt,
                'mac' => hash_hmac('SHA256', $expiresAt, base64_decode(substr(config('app.key'), 7)))
            ])),
            60
        );
    }
}
