<?php

namespace Illuminate\Tests\Integration\Redis;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\RedisManager;
use Orchestra\Testbench\TestCase;

class PhpRedisAclTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        try {
            $this->redis['phpredis']->connection()->client()->rawCommand('ACL', 'DELUSER', 'limited');
        } catch (\Throwable) {
            //
        }

        $this->tearDownRedis();
        parent::tearDown();
    }

    public function testEvalThrowsWhenAclForbidsCommandInsideScript()
    {
        $admin = $this->redis['phpredis']->connection();

        $admin->client()->rawCommand(
            'ACL', 'SETUSER', 'limited', 'on', '>secret', '~*', '&*', '-@all', '+eval', '+select'
        );

        $config = [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'username' => 'limited',
            'password' => 'secret',
            'database' => 0,
        ];

        $manager = new RedisManager($this->app, 'phpredis', ['default' => $config]);

        $this->expectException(\RedisException::class);

        $manager->connection()->eval(
            "return redis.call('rpush', KEYS[1], ARGV[1])", 1, 'acl:test', 'hello'
        );
    }

    public function testEvalReturnsFalseWhenScriptReturnsNilWithoutError()
    {
        $result = $this->redis['phpredis']->connection()->eval('return false', 0);

        $this->assertFalse($result);
    }
}
