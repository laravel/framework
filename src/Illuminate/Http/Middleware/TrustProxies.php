<?php

namespace Illuminate\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrustProxies
{
    /**
     * The trusted proxies for the application.
     *
     * @var array|string|null
     */
    protected $proxies;

    /**
     * The proxy header mappings.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $request::setTrustedProxies([], $this->getTrustedHeaderNames());

        $this->setTrustedProxyIpAddresses($request);

        return $next($request);
    }

    /**
     * Sets the trusted proxies on the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function setTrustedProxyIpAddresses(Request $request)
    {
        $trustedIps = $this->proxies() ?: config('trustedproxy.proxies');

        if ($trustedIps === '*' || $trustedIps === '**') {
            return $this->setTrustedProxyIpAddressesToTheCallingIp($request);
        }

        $trustedIps = is_string($trustedIps)
                ? array_map('trim', explode(',', $trustedIps))
                : $trustedIps;

        if (is_array($trustedIps)) {
            return $this->setTrustedProxyIpAddressesToSpecificIps($request, $trustedIps);
        }
    }

    /**
     * Specify the IP addresses to trust explicitly.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $trustedIps
     * @return void
     */
    protected function setTrustedProxyIpAddressesToSpecificIps(Request $request, array $trustedIps)
    {
        $request->setTrustedProxies($trustedIps, $this->getTrustedHeaderNames());
    }

    /**
     * Set the trusted proxy to be the IP address calling this servers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function setTrustedProxyIpAddressesToTheCallingIp(Request $request)
    {
        $request->setTrustedProxies([$request->server->get('REMOTE_ADDR')], $this->getTrustedHeaderNames());
    }

    /**
     * Retrieve trusted header name(s), falling back to defaults if config not set.
     *
     * @return int A bit field of Request::HEADER_*, to set which headers to trust from your proxies.
     */
    protected function getTrustedHeaderNames()
    {
        switch ($this->headers) {
            case 'HEADER_X_FORWARDED_AWS_ELB':
            case Request::HEADER_X_FORWARDED_AWS_ELB:
                return Request::HEADER_X_FORWARDED_AWS_ELB;

            case 'HEADER_FORWARDED':
            case Request::HEADER_FORWARDED:
                return Request::HEADER_FORWARDED;

            case 'HEADER_X_FORWARDED_FOR':
            case Request::HEADER_X_FORWARDED_FOR:
                return Request::HEADER_X_FORWARDED_FOR;

            case 'HEADER_X_FORWARDED_HOST':
            case Request::HEADER_X_FORWARDED_HOST:
                return Request::HEADER_X_FORWARDED_HOST;

            case 'HEADER_X_FORWARDED_PORT':
            case Request::HEADER_X_FORWARDED_PORT:
                return Request::HEADER_X_FORWARDED_PORT;

            case 'HEADER_X_FORWARDED_PROTO':
            case Request::HEADER_X_FORWARDED_PROTO:
                return Request::HEADER_X_FORWARDED_PROTO;

            default:
                return Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB;
        }

        return $this->headers;
    }

    /**
     * Get the trusted proxies.
     *
     * @return array|string|null
     */
    protected function proxies()
    {
        return $this->proxies;
    }
}
