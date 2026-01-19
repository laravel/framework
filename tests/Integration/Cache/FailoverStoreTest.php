<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Cache\Events\CacheFailedOver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

#[WithConfig('cache.default', 'failover')]
#[WithConfig('cache.stores.array.serialize', false)]
class FailoverStoreTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        CantSerialize::$throwException = true;
    }

    public function testFailoverCacheDispatchesEventOnlyOnce()
    {
        config([
            'cache.stores.failing_array' => array_merge(config('cache.stores.array'), ['serialize' => true]),
        ]);

        config([
            'cache.stores.failover.stores' => ['failing_array', 'array'],
        ]);

        Event::fake();

        Cache::put('irrelevant', new CantSerialize());

        Event::assertDispatched(CacheFailedOver::class, function (CacheFailedOver $event) {
            return $event->storeName === 'failing_array';
        });
        $this->assertInstanceOf(CantSerialize::class, Cache::store('array')->get('irrelevant'));

        Cache::put('irrelevant2', new CantSerialize());
        Event::assertDispatchedTimes(CacheFailedOver::class, 1);
        CantSerialize::$throwException = false;
        Cache::put('irrelevant3', new CantSerialize());
        Event::assertDispatchedTimes(CacheFailedOver::class, 1);
        CantSerialize::$throwException = true;
        Cache::put('irrelevant4', new CantSerialize());
        Event::assertDispatchedTimes(CacheFailedOver::class, 2);
    }
}

class CantSerialize
{
    public static bool $throwException = true;

    public function __serialize()
    {
        if (self::$throwException) {
            throw new \Exception('You cannot serialize this.');
        }

        return [];
    }
}
