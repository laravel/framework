<?php

namespace Illuminate\Tests\Session;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Session\SessionManager;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class SessionManagerTest extends TestCase
{
    public function testSetDefaultDriverAcceptsBackedEnum()
    {
        $app = new Container;
        $app->singleton('config', fn () => new Config(['session' => ['driver' => 'array']]));

        $manager = new SessionManager($app);
        $manager->setDefaultDriver(SessionDriverName::Array);

        $this->assertSame('array', $app['config']['session.driver']);
    }

    public function testRedisDriverUsesConfiguredSessionPrefix()
    {
        $app = new Container;
        $app->singleton('config', fn () => new Config([
            'session' => ['driver' => 'redis', 'lifetime' => 120, 'prefix' => 'some_custom_prefix'],
            'cache' => ['prefix' => 'cache_prefix', 'stores' => ['redis' => ['driver' => 'redis']]],
        ]));
        $app->singleton('cache', fn ($app) => new CacheManager($app));
        $app->instance('redis', m::mock(RedisFactory::class));

        $manager = new SessionManager($app);

        $this->assertSame('some_custom_prefix', $manager->driver()->getHandler()->getCache()->getStore()->getPrefix());
    }
}

enum SessionDriverName: string
{
    case Array = 'array';
}
