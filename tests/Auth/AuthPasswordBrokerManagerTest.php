<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class AuthPasswordBrokerManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBrokerCanResolveBackedEnum(): void
    {
        $app = $this->getApp();

        $broker = m::mock(PasswordBroker::class);

        $manager = m::mock(PasswordBrokerManager::class, [$app])->makePartial()->shouldAllowMockingProtectedMethods();
        $manager->shouldReceive('resolve')->with('users')->andReturn($broker);

        $result1 = $manager->broker(PasswordBrokerName::Users);
        $result2 = $manager->broker('users');

        $this->assertSame($broker, $result1);
        $this->assertSame($result1, $result2);
    }

    public function testSetDefaultDriverAcceptsBackedEnum(): void
    {
        $app = $this->getApp();

        $manager = new PasswordBrokerManager($app);
        $manager->setDefaultDriver(PasswordBrokerName::Users);

        $this->assertSame('users', $app['config']['auth.defaults.passwords']);
    }

    protected function getApp(): Container
    {
        $app = new Container;

        $app->singleton('config', fn () => new Config([
            'auth' => [
                'defaults' => ['passwords' => 'users'],
                'passwords' => [
                    'users' => [
                        'provider' => 'users',
                        'table' => 'password_reset_tokens',
                        'expire' => 60,
                        'throttle' => 60,
                    ],
                ],
            ],
        ]));

        return $app;
    }
}

enum PasswordBrokerName: string
{
    case Users = 'users';
}
