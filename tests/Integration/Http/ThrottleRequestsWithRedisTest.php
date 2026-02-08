<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

#[WithConfig('hashing.driver', 'bcrypt')]
class ThrottleRequestsWithRedisTest extends TestCase
{
    use InteractsWithRedis;

    public function testLockOpensImmediatelyAfterDecay()
    {
        $this->ifRedisAvailable(function () {
            $now = Carbon::now();

            Carbon::setTestNow($now);

            Route::get('/', function () {
                return 'yes';
            })->middleware(ThrottleRequestsWithRedis::class.':2,1');

            $response = $this->withoutExceptionHandling()->get('/');
            $this->assertSame('yes', $response->getContent());
            $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
            $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

            $response = $this->withoutExceptionHandling()->get('/');
            $this->assertSame('yes', $response->getContent());
            $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
            $this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));

            Carbon::setTestNow($finish = $now->addSeconds(58));

            try {
                $this->withoutExceptionHandling()->get('/');
            } catch (Throwable $e) {
                $this->assertEquals(429, $e->getStatusCode());
                $this->assertEquals(2, $e->getHeaders()['X-RateLimit-Limit']);
                $this->assertEquals(0, $e->getHeaders()['X-RateLimit-Remaining']);
                // $this->assertTrue(in_array($e->getHeaders()['Retry-After'], [2, 3]));
                // $this->assertTrue(in_array($e->getHeaders()['X-RateLimit-Reset'], [$finish->getTimestamp() + 2, $finish->getTimestamp() + 3]));
            }
        });
    }

    public static function keyPrefixProvider()
    {
        return [
            'default prefix' => [ThrottleRequestsWithRedis::class, 'throttle:'],
            'override prefix' => [ThrottleRequestsWithRedisWithCustomPrefix::class, 'prefix:'],
            'no prefix' => [ThrottleRequestsWithRedisWithNoPrefix::class, ''],
        ];
    }

    #[DataProvider('keyPrefixProvider')]
    public function testThrottleKeysArePrefixed($middleware, $expectedPrefix)
    {
        $this->ifRedisAvailable(function () use ($middleware, $expectedPrefix) {
            Route::get('/', function () {
                return 'Throttle test';
            })->middleware($middleware.':2,1');

            $this->withoutExceptionHandling()->get('/');

            $keys = $this->app->make('redis')->connection()->keys('*');

            $this->assertNotEmpty($keys, 'Throttle keys should exist in Redis');

            if ($expectedPrefix !== '') {
                $this->assertNotEmpty(
                    array_filter($keys, fn ($key) => str_contains($key, $expectedPrefix)),
                    "Throttle keys should contain the prefix '{$expectedPrefix}'"
                );
            } else {
                $this->assertEmpty(
                    array_filter($keys, fn ($key) => str_contains($key, 'throttle:')),
                    'Throttle keys should not have a prefix when prefix is empty'
                );
            }
        });
    }
}

class ThrottleRequestsWithRedisWithCustomPrefix extends ThrottleRequestsWithRedis
{
    protected function keyPrefix()
    {
        return 'prefix:';
    }
}

class ThrottleRequestsWithRedisWithNoPrefix extends ThrottleRequestsWithRedis
{
    protected function keyPrefix()
    {
        return '';
    }
}
