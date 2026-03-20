<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Idempotent
{
    /**
     * The cache store instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new middleware instance.
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Specify the idempotency options for the middleware.
     *
     * @return string
     *
     * @named-arguments-supported
     */
    public static function using(
        int $ttl = 86400,
        bool $required = true,
        string $scope = 'user',
        string $header = 'Idempotency-Key',
    ): string {
        return static::class.':'.implode(',', [
            $ttl,
            $required ? '1' : '0',
            $scope,
            $header,
        ]);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $ttl
     * @param  string  $required
     * @param  string  $scope
     * @param  string  $header
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(
        $request,
        Closure $next,
        $ttl = 86400,
        $required = '1',
        $scope = 'user',
        $header = 'Idempotency-Key',
    ) {
        $ttl = (int) $ttl;
        $required = filter_var($required, FILTER_VALIDATE_BOOLEAN);

        if (! $this->isIdempotentMethod($request)) {
            return $next($request);
        }

        $clientKey = $request->header($header);

        if (is_null($clientKey) || $clientKey === '') {
            if ($required) {
                throw new HttpException(400, "Missing required header: {$header}");
            }

            return $next($request);
        }

        $scopePrefix = $this->resolveScope($request, $scope);
        $routeIdentity = $this->resolveRouteIdentity($request);
        $storageKey = $this->buildStorageKey($routeIdentity, $request->method(), $scopePrefix, $header, $clientKey);
        $fingerprint = $this->buildFingerprint($request, $routeIdentity);
        $lockKey = 'idempotent-lock:'.$storageKey;
        $cacheKey = 'idempotent-response:'.$storageKey;

        $stored = $this->cache->get($cacheKey);

        if ($stored) {
            if ($stored['fingerprint'] !== $fingerprint) {
                throw new HttpException(422, 'Idempotency key already used with different request parameters.');
            }

            return $this->replayResponse($stored);
        }

        $lock = $this->cache->lock($lockKey, 10);

        if (! $lock->get()) {
            throw new HttpException(409, 'A request with this idempotency key is currently being processed.', null, ['Retry-After' => '1']);
        }

        $request->attributes->set('idempotent', true);
        $request->attributes->set('idempotency-key', $clientKey);

        try {
            $response = $next($request);

            $this->cache->put($cacheKey, [
                'fingerprint' => $fingerprint,
                'status' => $response->getStatusCode(),
                'headers' => $this->serializableHeaders($response),
                'content' => $response->getContent(),
                'is_redirect' => $response instanceof RedirectResponse,
                'target_url' => $response instanceof RedirectResponse ? $response->getTargetUrl() : null,
            ], $ttl);

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * Determine if the request method should be idempotency-managed.
     */
    protected function isIdempotentMethod(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH']);
    }

    /**
     * Resolve the scope prefix for the idempotency key.
     */
    protected function resolveScope(Request $request, string $scope): string
    {
        return match ($scope) {
            'user' => $this->resolveUserScope($request),
            'ip' => 'ip:'.$request->ip(),
            'global' => 'global',
            default => 'global',
        };
    }

    /**
     * Resolve the user scope, falling back to IP for guests.
     */
    protected function resolveUserScope(Request $request): string
    {
        $user = $request->user();

        return $user
            ? 'user:'.$user->getAuthIdentifier()
            : 'ip:'.$request->ip();
    }

    /**
     * Resolve a stable route identity string.
     */
    protected function resolveRouteIdentity(Request $request): string
    {
        $route = $request->route();

        return match (true) {
            $route && $route->getName() !== null => $route->getName(),
            $route !== null => ($route->getDomain() ?? '').'/'.$route->uri(),
            default => $request->getPathInfo(),
        };
    }

    /**
     * Build the storage key for the idempotency record.
     */
    protected function buildStorageKey(
        string $routeIdentity,
        string $method,
        string $scopePrefix,
        string $header,
        string $clientKey,
    ): string {
        return hash('xxh128', implode('|', [
            $routeIdentity,
            $method,
            $scopePrefix,
            $header,
            $clientKey,
        ]));
    }

    /**
     * Build a request fingerprint from method, route, query, and payload.
     */
    protected function buildFingerprint(Request $request, string $routeIdentity): string
    {
        $payloadHash = $this->hashPayload($request);

        return hash('xxh128', implode('|', [
            strtoupper($request->method()),
            $routeIdentity,
            $request->getQueryString() ?? '',
            $payloadHash,
            $request->getContentTypeFormat() ?? '',
        ]));
    }

    /**
     * Hash the request payload, normalizing JSON key order.
     */
    protected function hashPayload(Request $request): string
    {
        if ($request->isJson()) {
            $decoded = json_decode($request->getContent(), true);

            if (is_array($decoded)) {
                $this->recursiveKeySort($decoded);

                return hash('xxh128', json_encode($decoded));
            }
        }

        return hash('xxh128', $request->getContent());
    }

    /**
     * Recursively sort an array by keys.
     */
    protected function recursiveKeySort(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveKeySort($value);
            }
        }
    }

    /**
     * Get serializable headers from a response.
     */
    protected function serializableHeaders(SymfonyResponse $response): array
    {
        $headers = $response->headers->all();

        unset($headers['date'], $headers['set-cookie']);

        return $headers;
    }

    /**
     * Replay a cached response.
     */
    protected function replayResponse(array $stored): SymfonyResponse
    {
        if ($stored['is_redirect']) {
            $response = new RedirectResponse(
                $stored['target_url'],
                $stored['status'],
                $stored['headers'],
            );
        } else {
            $response = new Response(
                $stored['content'],
                $stored['status'],
                $stored['headers'],
            );
        }

        $response->headers->set('Idempotency-Replayed', 'true');

        return $response;
    }
}
