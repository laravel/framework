<?php

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Foundation\Application;
use Mockery as m;
use Mockery\MockInterface;

class AuthExceptionsTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testMissingGuardConfiguration()
    {
        /** @var MockInterface|Application $app */
        $app = m::mock('Illuminate\Foundation\Application');

        $app->shouldReceive('offsetGet')
            ->with('config')
            ->andReturn($app);

        $app->shouldReceive('offsetGet')
            ->once()
            ->with('auth.guards.missing')
            ->andReturn(null);

        /** @var MockInterface|AuthManager $auth */
        $auth = m::mock('Illuminate\Auth\AuthManager', [$app]);

        $throws = false;

        try {
            $auth->resolve('missing');
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();

            $this->assertContains('This is usually caused by', $message);
            $this->assertContains('You could try', $message);
            $this->assertContains('Need more documentation', $message);

            $throws = true;
        }

        $this->assertTrue($throws);
    }
}
