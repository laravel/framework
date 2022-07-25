<?php

namespace Illuminate\Support\Facades;

use Illuminate\Http\Client\Factory;

/**
 * @method static \GuzzleHttp\Promise\PromiseInterface response($body = null, $status = 200, $headers = [])
 * @method static \Illuminate\Http\Client\PendingRequest accept(string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest acceptJson()
 * @method static \Illuminate\Http\Client\PendingRequest asForm()
 * @method static \Illuminate\Http\Client\PendingRequest asJson()
 * @method static \Illuminate\Http\Client\PendingRequest asMultipart()
 * @method static \Illuminate\Http\Client\PendingRequest async()
 * @method static \Illuminate\Http\Client\PendingRequest attach(string|array $name, string|resource $contents = '', string|null $filename = null, array $headers = [])
 * @method static \Illuminate\Http\Client\PendingRequest baseUrl(string $url)
 * @method static \Illuminate\Http\Client\PendingRequest beforeSending(callable $callback)
 * @method static \Illuminate\Http\Client\PendingRequest bodyFormat(string $format)
 * @method static \Illuminate\Http\Client\PendingRequest connectTimeout(int $seconds)
 * @method static \Illuminate\Http\Client\PendingRequest contentType(string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest dd()
 * @method static \Illuminate\Http\Client\PendingRequest dump()
 * @method static \Illuminate\Http\Client\PendingRequest maxRedirects(int $max)
 * @method static \Illuminate\Http\Client\PendingRequest retry(int $times, int $sleepMilliseconds = 0, ?callable $when = null, bool $throw = true)
 * @method static \Illuminate\Http\Client\ResponseSequence sequence(array $responses = [])
 * @method static \Illuminate\Http\Client\PendingRequest sink(string|resource $to)
 * @method static \Illuminate\Http\Client\PendingRequest stub(callable $callback)
 * @method static \Illuminate\Http\Client\PendingRequest timeout(int $seconds)
 * @method static \Illuminate\Http\Client\PendingRequest withBasicAuth(string $username, string $password)
 * @method static \Illuminate\Http\Client\PendingRequest withBody(resource|string $content, string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest withCookies(array $cookies, string $domain)
 * @method static \Illuminate\Http\Client\PendingRequest withDigestAuth(string $username, string $password)
 * @method static \Illuminate\Http\Client\PendingRequest withHeaders(array $headers)
 * @method static \Illuminate\Http\Client\PendingRequest withMiddleware(callable $middleware)
 * @method static \Illuminate\Http\Client\PendingRequest withOptions(array $options)
 * @method static \Illuminate\Http\Client\PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method static \Illuminate\Http\Client\PendingRequest withUserAgent(string $userAgent)
 * @method static \Illuminate\Http\Client\PendingRequest withoutRedirecting()
 * @method static \Illuminate\Http\Client\PendingRequest withoutVerifying()
 * @method static \Illuminate\Http\Client\PendingRequest throw(callable $callback = null)
 * @method static \Illuminate\Http\Client\PendingRequest throwIf($condition)
 * @method \Illuminate\Http\Client\PendingRequest throwUnless($condition)
 * @method static array pool(callable $callback)
 * @method static \Illuminate\Http\Client\Response delete(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response get(string $url, array|string|null $query = null)
 * @method static \Illuminate\Http\Client\Response head(string $url, array|string|null $query = null)
 * @method static \Illuminate\Http\Client\Response patch(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response post(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response put(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response send(string $method, string $url, array $options = [])
 * @method static \Illuminate\Http\Client\Factory allowStrayRequests()
 * @method static void assertSent(callable $callback)
 * @method static void assertSentInOrder(array $callbacks)
 * @method static void assertNotSent(callable $callback)
 * @method static void assertNothingSent()
 * @method static void assertSentCount(int $count)
 * @method static void assertSequencesAreEmpty()
 *
 * @see \Illuminate\Http\Client\Factory
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param  \Closure|array  $callback
     * @return \Illuminate\Http\Client\Factory
     */
    public static function fake($callback = null)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($callback) {
            static::swap($fake->fake($callback));
        });
    }

    /**
     * Register a response sequence for the given URL pattern.
     *
     * @param  string  $urlPattern
     * @return \Illuminate\Http\Client\ResponseSequence
     */
    public static function fakeSequence(string $urlPattern = '*')
    {
        $fake = tap(static::getFacadeRoot(), function ($fake) {
            static::swap($fake);
        });

        return $fake->fakeSequence($urlPattern);
    }

    /**
     * Indicate that an exception should be thrown if any request is not faked.
     *
     * @return \Illuminate\Http\Client\Factory
     */
    public static function preventStrayRequests()
    {
        return tap(static::getFacadeRoot(), function ($fake) {
            static::swap($fake->preventStrayRequests());
        });
    }

    /**
     * Stub the given URL using the given callback.
     *
     * @param  string  $url
     * @param  \Illuminate\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface|callable  $callback
     * @return \Illuminate\Http\Client\Factory
     */
    public static function stubUrl($url, $callback)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($url, $callback) {
            static::swap($fake->stubUrl($url, $callback));
        });
    }
}
