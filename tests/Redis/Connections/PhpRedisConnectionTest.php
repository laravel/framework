<?php

namespace Illuminate\Tests\Redis\Connections;

use PHPUnit\Framework\TestCase;
use Illuminate\Tests\Redis\InteractsWithRedis;

class PhpRedisConnectionTest extends TestCase
{
    use InteractsWithRedis;

    public function setUp()
    {
        parent::setUp();
        $this->setUpRedis();

        if (! isset($this->redis['phpredis'])) {
            $this->markTestSkipped('PhpRedis should be enabled to run the tests');
        }
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->tearDownRedis();
    }

    public function testPhpRedisPipeline()
    {
        $result = $this->redis['phpredis']->connection()->pipeline(function ($pipe) {
            $pipe->set('test:pipeline:1', 1);
            $pipe->get('test:pipeline:1');
            $pipe->set('test:pipeline:2', 2);
            $pipe->get('test:pipeline:2');
        });

        $this->assertCount(4, $result);
        $this->assertEquals(1, $result[1]);
        $this->assertEquals(2, $result[3]);
    }

    public function testPhpRedisTransaction()
    {
        $result = $this->redis['phpredis']->connection()->transaction(function ($pipe) {
            $pipe->set('test:transaction:1', 1);
            $pipe->get('test:transaction:1');
            $pipe->set('test:transaction:2', 2);
            $pipe->get('test:transaction:2');
        });

        $this->assertCount(4, $result);
        $this->assertEquals(1, $result[1]);
        $this->assertEquals(2, $result[3]);
    }

    public function testPhpRedisExecuteRaw()
    {
        $this->redis['phpredis']->connection()->set('test:raw:1', 1);

        $this->assertEquals(
            1, $this->redis['phpredis']->connection()->executeRaw(['GET', 'test:raw:1'])
        );
    }
}
