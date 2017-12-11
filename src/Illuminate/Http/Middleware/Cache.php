<?php

namespace Illuminate\Http\Middleware;

use Closure;

class Cache
{
    /**
     * Add cache related HTTP headers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|null  $maxAge
     * @param  int|null  $sharedMaxAge
     * @param  bool|null $public
     * @param  bool|null $etag
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, Closure $next, int $maxAge = null, int $sharedMaxAge = null, bool $public = null, bool $etag = false)
    {
        /**
         * @var   \Symfony\Component\HttpFoundation\Response
         */
        $response = $next($request);

        if (! $request->isMethodCacheable() || ! $response->getContent()) {
            return $response;
        }

        if (! $response->getContent()) {
            return;
        }
        if ($etag) {
            $response->setEtag(md5($response->getContent()));
        }
        if (null !== $maxAge) {
            $response->setMaxAge($maxAge);
        }
        if (null !== $sharedMaxAge) {
            $response->setSharedMaxAge($sharedMaxAge);
        }
        if (null !== $public) {
            $public ? $response->setPublic() : $response->setPrivate();
        }

        return $response;
    }
}
