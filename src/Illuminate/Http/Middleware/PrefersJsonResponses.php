<?php

namespace Illuminate\Http\Middleware;

use Closure;

class PrefersJsonResponses
{
    /**
     * Handle an incoming request.
     *
     * This middleware mutates the incoming request: when the "Accept" header
     * expresses no specific media-type preference, it is rewritten to
     * "application/json" so downstream consumers (exception handler,
     * auth/validation middleware, content negotiation) return JSON. The
     * original value is preserved on the "X-Original-Accept" header.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $accept = $request->headers->get('Accept');

        if ($this->acceptHeaderIsBroad($accept)) {
            if ($accept !== null) {
                $request->headers->set('X-Original-Accept', $accept);
            }

            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }

    /**
     * Determine if the given "Accept" header value is broad enough to be treated as JSON.
     *
     * The header is considered broad when it is missing, empty, or every
     * media-type it lists is a wildcard ("*\/*" or "application/*"). A mixed
     * header that contains any specific media-type is left alone so that the
     * client's preference wins.
     *
     * @param  string|null  $accept
     * @return bool
     */
    protected function acceptHeaderIsBroad($accept)
    {
        if ($accept === null || trim($accept) === '') {
            return true;
        }

        foreach (explode(',', $accept) as $value) {
            $value = strtolower(trim($value));

            if ($value === '') {
                continue;
            }

            $pos = strpos($value, ';');

            if ($pos !== false) {
                $value = trim(substr($value, 0, $pos));
            }

            if (! in_array($value, ['*/*', 'application/*'], true)) {
                return false;
            }
        }

        return true;
    }
}
