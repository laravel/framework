<?php

namespace Illuminate\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class HandleIdempotencyKeys
{
    /**
     * The name of the header that carries the idempotency key.
     *
     * @var string
     */
    const HEADER = 'Idempotency-Key';

    /**
     * The default number of seconds a response may be replayed.
     *
     * @var int
     */
    const DEFAULT_TTL = 86400;

    /**
     * The number of seconds a request may hold an idempotency key while it is being processed.
     *
     * @var int
     */
    protected $lockSeconds = 60;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     */
    public function __construct(protected Cache $cache)
    {
    }

    /**
     * Specify the options for the middleware.
     *
     * @param  int  $ttl
     * @param  bool  $required
     * @return string
     */
    public static function using($ttl = self::DEFAULT_TTL, $required = false)
    {
        return static::class.':'.$ttl.($required ? ',required' : '');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$options
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next, ...$options)
    {
        if ($request->isMethodIdempotent()) {
            return $next($request);
        }

        if (is_null($key = $request->header(static::HEADER))) {
            if (in_array('required', $options, true)) {
                throw new BadRequestHttpException('The '.static::HEADER.' header is required.');
            }

            return $next($request);
        }

        $key = $this->cacheKey($request, $this->validateKey($key));

        $fingerprint = $this->fingerprint($request);

        if ($stored = $this->cache->get($key)) {
            return $this->replay($stored, $fingerprint);
        }

        $lock = $this->cache->lock($key.':lock', $this->lockSeconds);

        if (! $lock->get()) {
            throw new ConflictHttpException('A request with this idempotency key is currently being processed.');
        }

        try {
            if ($stored = $this->cache->get($key)) {
                return $this->replay($stored, $fingerprint);
            }

            $response = $next($request);

            if ($this->shouldStore($response)) {
                $this->cache->put($key, [
                    'fingerprint' => $fingerprint,
                    'status' => $response->getStatusCode(),
                    'headers' => $response->headers->allPreserveCaseWithoutCookies(),
                    'content' => $response->getContent(),
                ], $this->ttl($options));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * Validate the given idempotency key and return its normalized value.
     *
     * @param  string  $key
     * @return string
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    protected function validateKey($key)
    {
        $key = Str::unwrap(trim($key), '"');

        if ($key === '' || strlen($key) > 255) {
            throw new BadRequestHttpException('The '.static::HEADER.' header must be between 1 and 255 characters.');
        }

        return $key;
    }

    /**
     * Get the cache key for the given request and idempotency key.
     *
     * Keys are scoped to the authenticated user, or to the client's IP address for guests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return string
     */
    protected function cacheKey($request, $key)
    {
        $scope = ($user = $request->user())
            ? $user::class.'|'.$user->getAuthIdentifier()
            : $request->ip();

        return 'idempotency:'.hash('sha256', $scope.'|'.$key);
    }

    /**
     * Get a fingerprint of the request that uniquely identifies its payload.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function fingerprint($request)
    {
        $files = $request->allFiles();

        array_walk_recursive($files, function (&$file) {
            $file = $file->isValid() ? hash_file('xxh128', $file->getRealPath()) : $file->getError();
        });

        return hash('xxh128', serialize([
            $request->method(), $request->path(), $request->input(), $files,
        ]));
    }

    /**
     * Replay the given stored response.
     *
     * @param  array  $stored
     * @param  string  $fingerprint
     * @return \Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    protected function replay(array $stored, $fingerprint)
    {
        if ($stored['fingerprint'] !== $fingerprint) {
            throw new UnprocessableEntityHttpException('The idempotency key has already been used for a different request.');
        }

        return (new Response($stored['content'], $stored['status'], $stored['headers']))
            ->header('Idempotent-Replayed', 'true');
    }

    /**
     * Determine if the given response should be stored for replaying.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    protected function shouldStore($response)
    {
        return ($response->isSuccessful() || $response->isRedirection())
            && ! isset($response->exception)
            && $response->getContent() !== false;
    }

    /**
     * Get the number of seconds a response should be stored for.
     *
     * @param  array  $options
     * @return int
     */
    protected function ttl(array $options)
    {
        foreach ($options as $option) {
            if (is_numeric($option)) {
                return (int) $option;
            }
        }

        return static::DEFAULT_TTL;
    }
}
