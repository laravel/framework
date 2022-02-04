<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use stdClass;
use const INF;

class RedisStoreTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    public function testItCanStoreNan()
    {
        Cache::store('redis')->clear();

        $result = Cache::store('redis')->put('foo', NAN);
        $this->assertTrue($result);
        $this->assertNan(Cache::store('redis')->get('foo'));
    }

    /**
     * @dataProvider valuesDataProvider
     */
    public function testValues(string $key, $value)
    {
        Cache::store('redis')->clear();

        $result = Cache::store('redis')->put($key, $value);
        $this->assertTrue($result);

        $cache = Cache::store('redis')->get($key);
        if (is_scalar($value)) {
            $this->assertSame($value, $cache);
        } else {
            $this->assertEquals($value, $cache);
        }
    }

    public function valuesDataProvider(): array
    {
        return [
            ['string', 'string'],
            ['string-int', '1'],
            ['string-float', '1.1'],
            ['array', []],
            ['bool', true],
            ['bool-false', false],
            ['int', 1],
            ['float', 1.2,],
            ['float-int', 1.0],
            ['float-e', 7E+20],
            ['float-ne', 7E-20],
            ['float-inf', INF],
            ['float-ninf', -INF],
            ['null', null],
            ['object', new stdClass()],
        ];
    }
}
