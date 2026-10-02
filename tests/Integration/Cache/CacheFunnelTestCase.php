<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Cache\Limiters\LimiterTimeoutException;
use Illuminate\Contracts\Cache\Repository;
use Orchestra\Testbench\TestCase;
use Throwable;

abstract class CacheFunnelTestCase extends TestCase
{
    abstract protected function cache(): Repository;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->releaseFunnelLocks();
        } catch (Throwable) {
        }
    }

    public function testFunnelBasicHappyPath()
    {
        $result = $this->cache()->funnel('test')
            ->limit(2)
            ->releaseAfter(60)
            ->block(0)
            ->then(fn () => 'hello');

        $this->assertSame('hello', $result);
    }

    public function testFunnelReleasesLockAfterCallback()
    {
        for ($i = 0; $i < 5; $i++) {
            $result = $this->cache()->funnel('test')
                ->limit(1)
                ->releaseAfter(60)
                ->block(0)
                ->then(fn () => 'ok');

            $this->assertSame('ok', $result);
        }
    }

    public function testFunnelLockReleasedOnException()
    {
        try {
            $this->cache()->funnel('test')
                ->limit(1)
                ->releaseAfter(60)
                ->block(0)
                ->then(function () {
                    throw new \Exception('fail');
                });
        } catch (\Exception) {
        }

        $result = $this->cache()->funnel('test')
            ->limit(1)
            ->releaseAfter(60)
            ->block(0)
            ->then(fn () => 'recovered');

        $this->assertSame('recovered', $result);
    }

    public function testFunnelTimeoutExceptionWithoutFailureCallback()
    {
        $this->cache()->lock('test1', 60)->get();
        $this->cache()->lock('test2', 60)->get();

        $this->expectException(LimiterTimeoutException::class);

        $this->cache()->funnel('test')
            ->limit(2)
            ->releaseAfter(60)
            ->block(0)
            ->then(fn () => 'should not run');
    }

    public function testFunnelFailureCallbackReceivesException()
    {
        $this->cache()->lock('test1', 60)->get();
        $this->cache()->lock('test2', 60)->get();

        $result = $this->cache()->funnel('test')
            ->limit(2)
            ->releaseAfter(60)
            ->block(0)
            ->then(
                fn () => 'should not run',
                function ($e) {
                    $this->assertInstanceOf(LimiterTimeoutException::class, $e);

                    return 'failed';
                }
            );

        $this->assertSame('failed', $result);
    }

    public function testFunnelIndependentKeys()
    {
        $this->cache()->lock('key-a1', 60)->get();

        $result = $this->cache()->funnel('key-b')
            ->limit(1)
            ->releaseAfter(60)
            ->block(0)
            ->then(fn () => 'key-b-ok');

        $this->assertSame('key-b-ok', $result);
    }

    protected function releaseFunnelLocks(): void
    {
        $this->cache()->lock('test1')->forceRelease();
        $this->cache()->lock('test2')->forceRelease();
        $this->cache()->lock('key-a1')->forceRelease();
        $this->cache()->lock('key-b1')->forceRelease();
    }

    protected function tearDown(): void
    {
        try {
            $this->releaseFunnelLocks();
        } catch (Throwable) {
        }

        parent::tearDown();
    }
}
