<?php

namespace Illuminate\Services\Http;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthenticateService
{
    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     */
    public function __construct(protected Config $config)
    {
        //
    }

    /**
     * Ensure the request carries this application's service token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next)
    {
        $token = $this->config->get('services.token');

        if (! is_string($token) || $token === '' || ! hash_equals($token, (string) $request->bearerToken())) {
            throw new HttpException(401, 'Invalid service token.');
        }

        return $next($request);
    }
}
