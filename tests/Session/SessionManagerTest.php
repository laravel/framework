<?php

namespace Illuminate\Tests\Session;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Session\SessionManager;
use PHPUnit\Framework\TestCase;

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
}

enum SessionDriverName: string
{
    case Array = 'array';
}
