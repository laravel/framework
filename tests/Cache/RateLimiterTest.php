<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class RateLimiterTest extends TestCase
{
    public static function registerNamedRateLimiterDataProvider(): array
    {
        return [
            'uses BackedEnum' => [BackedEnumNamedRateLimiter::API, 'api'],
            'uses UnitEnum' => [UnitEnumNamedRateLimiter::THIRD_PARTY, 'THIRD_PARTY'],
            'uses normal string' => ['yolo', 'yolo'],
            'uses int' => [100, '100'],
        ];
    }

    #[DataProvider('registerNamedRateLimiterDataProvider')]
    public function testRegisterNamedRateLimiter(mixed $name, string $expected): void
    {
        $reflectedLimitersProperty = new ReflectionProperty(RateLimiter::class, 'limiters');

        $rateLimiter = new RateLimiter($this->createMock(Cache::class));
        $rateLimiter->for($name, fn () => Limit::perMinute(100));

        $limiters = $reflectedLimitersProperty->getValue($rateLimiter);

        $this->assertArrayHasKey($expected, $limiters);

        $limiterClosure = $rateLimiter->limiter($name);

        $this->assertNotNull($limiterClosure);
    }

    public function testShouldUseOriginKeyAsPrefixWhenMultipleLimiterWithSameKey()
    {
        $rateLimiter = new RateLimiter(new Repository(new ArrayStore));

        $rateLimiter->for('user_limiter', fn (string $userId) => [
            Limit::perSecond(3)->by($userId),
            Limit::perMinute(5)->by($userId),
        ]);

        $userId1 = '123';
        $userId2 = '456';

        $limiterForUser1 = $rateLimiter->limiter('user_limiter')($userId1);
        $limiterForUser2 = $rateLimiter->limiter('user_limiter')($userId2);

        for ($i = 0; $i < 3; $i++) {
            $this->assertFalse($rateLimiter->tooManyAttempts($limiterForUser1[0]->key, $limiterForUser1[0]->maxAttempts));
            $this->assertFalse($rateLimiter->tooManyAttempts($limiterForUser2[0]->key, $limiterForUser2[0]->maxAttempts));

            $rateLimiter->hit($limiterForUser1[0]->key, $limiterForUser1[0]->decaySeconds);
            $rateLimiter->hit($limiterForUser2[0]->key, $limiterForUser2[0]->decaySeconds);
        }

        $this->assertNotSame($limiterForUser1[0]->key, $limiterForUser2[0]->key);
        $this->assertNotSame($limiterForUser1[1]->key, $limiterForUser2[1]->key);
    }

    public function testRateLimiterResetsAfterDecay()
    {
        $store = new ArrayStore;
        $limiter = new RateLimiter(new Repository(new ArrayStore));

        $key = 'test-key';
        $maxAttempts = 3;
        $decaySeconds = 2; // short decay for fast testing

        // 1️⃣ Initially, there should be no attempts
        $this->assertFalse($limiter->tooManyAttempts($key, $maxAttempts));

        // 2️⃣ Hit the key until reaching maxAttempts
        $limiter->hit($key, $decaySeconds);
        $this->assertFalse($limiter->tooManyAttempts($key, $maxAttempts));

        $limiter->hit($key, $decaySeconds);
        $this->assertFalse($limiter->tooManyAttempts($key, $maxAttempts));

        $limiter->hit($key, $decaySeconds);
        // Now it should hit the limit
        $this->assertTrue($limiter->tooManyAttempts($key, $maxAttempts));

        // 3️⃣ Wait for decay period to expire
        sleep($decaySeconds + 1);

        // After decay, the attempts should reset
        $this->assertFalse($limiter->tooManyAttempts($key, $maxAttempts));

        // 4️⃣ Hitting again should work correctly
        $limiter->hit($key, $decaySeconds);
        $this->assertFalse($limiter->tooManyAttempts($key, $maxAttempts));

        // 5️⃣ Multiple hits after reset should also be tracked properly
        $limiter->hit($key, $decaySeconds);
        $limiter->hit($key, $decaySeconds);
        $this->assertTrue($limiter->tooManyAttempts($key, $maxAttempts));
    }
}

enum BackedEnumNamedRateLimiter: string
{
    case API = 'api';
}

enum UnitEnumNamedRateLimiter
{
    case THIRD_PARTY;
}
