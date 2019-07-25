<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * @group integration
 */
class DynamoDbStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! isset($_ENV['DYNAMODB_CACHE_TABLE'])) {
            $this->markTestSkipped('DynamoDB not configured.');
        }
    }

    public function test_items_can_be_stored_and_retrieved()
    {
        Cache::driver('dynamodb')->put('name', 'Taylor', 10);
        $this->assertEquals('Taylor', Cache::driver('dynamodb')->get('name'));

        Cache::driver('dynamodb')->put(['name' => 'Abigail', 'age' => 28], 10);
        $this->assertEquals('Abigail', Cache::driver('dynamodb')->get('name'));
        $this->assertEquals(28, Cache::driver('dynamodb')->get('age'));

        $this->assertEquals([
            'name' => 'Abigail',
            'age' => 28,
            'height' => null,
        ], Cache::driver('dynamodb')->many(['name', 'age', 'height']));

        Cache::driver('dynamodb')->forget('name');
        $this->assertNull(Cache::driver('dynamodb')->get('name'));
    }

    public function test_items_can_be_atomically_added()
    {
        $key = Str::random(6);

        $this->assertTrue(Cache::driver('dynamodb')->add($key, 'Taylor', 10));
        $this->assertFalse(Cache::driver('dynamodb')->add($key, 'Taylor', 10));
    }

    public function test_items_can_be_incremented_and_decremented()
    {
        Cache::driver('dynamodb')->put('counter', 0, 10);
        Cache::driver('dynamodb')->increment('counter');
        Cache::driver('dynamodb')->increment('counter', 4);

        $this->assertEquals(5, Cache::driver('dynamodb')->get('counter'));

        Cache::driver('dynamodb')->decrement('counter', 5);
        $this->assertEquals(0, Cache::driver('dynamodb')->get('counter'));
    }

    public function test_locks_can_be_aquired()
    {
        Cache::driver('dynamodb')->lock('lock', 10)->get(function () {
            $this->assertFalse(Cache::driver('dynamodb')->lock('lock', 10)->get());
        });
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'dynamodb');

        $app['config']->set('cache.stores.dynamodb', [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => 'us-east-1',
            'table' => env('DYNAMODB_CACHE_TABLE', 'laravel_test'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ]);
    }
}
