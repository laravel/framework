<?php

namespace Illuminate\Tests\Cache;

use Carbon\Carbon;
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

    public function testSlidingWindowHitIncrementsCounter()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 0));

        $rateLimiter = new RateLimiter(new Repository(new ArrayStore));

        $this->assertEquals(1, $rateLimiter->hit('test-key', 60, true));
        $this->assertEquals(2, $rateLimiter->hit('test-key', 60, true));
        $this->assertEquals(3, $rateLimiter->hit('test-key', 60, true));

        Carbon::setTestNow();
    }

    public function testSlidingWindowTooManyAttempts()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 0));

        $rateLimiter = new RateLimiter(new Repository(new ArrayStore));

        for ($i = 0; $i < 3; $i++) {
            $this->assertFalse($rateLimiter->tooManyAttempts('test-key', 3, 60, true));
            $rateLimiter->hit('test-key', 60, true);
        }

        $this->assertTrue($rateLimiter->tooManyAttempts('test-key', 3, 60, true));

        Carbon::setTestNow();
    }

    public function testSlidingWindowPreventsWindowBoundaryBurst()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 0));

        $rateLimiter = new RateLimiter(new Repository(new ArrayStore));

        // Make 3 requests at t=0 (within first window)
        for ($i = 0; $i < 3; $i++) {
            $rateLimiter->hit('test-key', 60, true);
        }

        // Move to t=61 (start of new window)
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 1, 1));

        // With sliding window, effective = floor((59/60) * 3) + 0 = floor(2.95) = 2
        // So we should still have some budget from the previous window weighing in
        $this->assertFalse($rateLimiter->tooManyAttempts('test-key', 3, 60, true));

        // Add 1 hit in new window
        $rateLimiter->hit('test-key', 60, true);

        // Now effective = floor((59/60) * 3) + 1 = 2 + 1 = 3, should be blocked
        $this->assertTrue($rateLimiter->tooManyAttempts('test-key', 3, 60, true));

        Carbon::setTestNow();
    }

    public function testSlidingWindowAllowsRequestsAfterTimeElapsed()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 0));

        $rateLimiter = new RateLimiter(new Repository(new ArrayStore));

        // Max out the limiter
        for ($i = 0; $i < 3; $i++) {
            $rateLimiter->hit('test-key', 60, true);
        }

        $this->assertTrue($rateLimiter->tooManyAttempts('test-key', 3, 60, true));

        // Move forward 90 seconds (into second window, with 30s elapsed)
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 1, 30));

        // effective = floor((30/60) * 3) + 0 = floor(1.5) = 1, under limit
        $this->assertFalse($rateLimiter->tooManyAttempts('test-key', 3, 60, true));

        Carbon::setTestNow();
    }

    public function testSlidingWindowRemaining()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 0));

        $rateLimiter = new RateLimiter(new Repository(new ArrayStore));

        $this->assertEquals(5, $rateLimiter->remaining('test-key', 5, 60, true));

        $rateLimiter->hit('test-key', 60, true);
        $rateLimiter->hit('test-key', 60, true);

        $this->assertEquals(3, $rateLimiter->remaining('test-key', 5, 60, true));

        Carbon::setTestNow();
    }

    public function testSlidingWindowAvailableIn()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 0));

        $rateLimiter = new RateLimiter(new Repository(new ArrayStore));

        $this->assertEquals(0, $rateLimiter->availableIn('test-key', 60, true));

        $rateLimiter->hit('test-key', 60, true);

        $this->assertEquals(60, $rateLimiter->availableIn('test-key', 60, true));

        // Move 20 seconds forward
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 20));

        $this->assertEquals(40, $rateLimiter->availableIn('test-key', 60, true));

        Carbon::setTestNow();
    }

    public function testSlidingWindowClear()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 0));

        $rateLimiter = new RateLimiter(new Repository(new ArrayStore));

        for ($i = 0; $i < 3; $i++) {
            $rateLimiter->hit('test-key', 60, true);
        }

        $this->assertTrue($rateLimiter->tooManyAttempts('test-key', 3, 60, true));

        $rateLimiter->clear('test-key');

        $this->assertFalse($rateLimiter->tooManyAttempts('test-key', 3, 60, true));
        $this->assertEquals(3, $rateLimiter->remaining('test-key', 3, 60, true));

        Carbon::setTestNow();
    }

    public function testSlidingWindowFullyExpiredAfterTwoWindows()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 0));

        $rateLimiter = new RateLimiter(new Repository(new ArrayStore));

        for ($i = 0; $i < 3; $i++) {
            $rateLimiter->hit('test-key', 60, true);
        }

        // Move forward 2 full windows (120 seconds)
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 2, 0));

        // Everything should be expired
        $this->assertFalse($rateLimiter->tooManyAttempts('test-key', 3, 60, true));
        $this->assertEquals(3, $rateLimiter->remaining('test-key', 3, 60, true));

        Carbon::setTestNow();
    }

    public function testSlidingWindowAttempt()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 0));

        $rateLimiter = new RateLimiter(new Repository(new ArrayStore));

        $this->assertSame('ok', $rateLimiter->attempt('test-key', 2, fn () => 'ok', 60, true));
        $this->assertSame('ok', $rateLimiter->attempt('test-key', 2, fn () => 'ok', 60, true));
        $this->assertFalse($rateLimiter->attempt('test-key', 2, fn () => 'fail', 60, true));

        Carbon::setTestNow();
    }

    public function testLimitSlidingWindowFluent()
    {
        $limit = Limit::perMinute(60)->by('user-1')->slidingWindow();

        $this->assertTrue($limit->slidingWindow);
        $this->assertEquals(60, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('user-1', $limit->key);
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
