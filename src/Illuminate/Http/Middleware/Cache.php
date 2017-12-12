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
     * @param  string|array  $options
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \InvalidArgumentException
     */
    public function handle($request, Closure $next, $options = [])
    {
        /**
         * @var $response \Symfony\Component\HttpFoundation\Response
         */
        $response = $next($request);
        if (! $request->isMethodCacheable() || ! $response->getContent()) {
            return $response;
        }

        if (\is_string($options)) {
            $parsedOptions = [];
            foreach (explode(';', $options) as $opt) {
                $data = explode('=', $opt, 2);
                $parsedOptions[$data[0]] = $data[1] ?? true;
            }

            $options = $parsedOptions;
        }

        if (isset($options['etag']) && true === $options['etag']) {
            $options['etag'] = md5($response->getContent());
        }

        $response->setCache($options);
        $response->isNotModified($request);

        return $response;
    }
}
