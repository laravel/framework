<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Config\Repository as Config;

class ValidateSignature
{
    /**
     * The configuration repository implementation.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, Closure $next)
    {
        $original = $request->url().'?'.http_build_query(Arr::except($request->query(), 'signature'));

        $hash = hash_hmac('sha256', $original, $this->config->get('app.key'));

        return $request->get('signature') === $hash && ! $this->expired($request)
                        ? $next($request)
                        : new Response('Invalid signature.', 401);
    }

    /**
     * Determine if the signed URL has expired.
     *
     * @param  \Illuminate\Http\Request
     * @return bool
     */
    protected function expired($request)
    {
        $expires = Arr::get($request->query(), 'expires');

        return $expires && Carbon::now()->getTimestamp() > $expires;
    }
}
