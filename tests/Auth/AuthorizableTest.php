<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Auth\User;
use PHPUnit\Framework\TestCase;
use Throwable;

class AuthorizableTest extends TestCase
{
    public function setUp(): void
    {
        $container = Container::setInstance(new Container());

        $gate = new Gate($container, fn () => null);
        $gate->policy(AuthorizableTestDummy::class, AuthorizableTestPolicy::class);

        $container->singleton(GateContract::class, fn () => $gate);
    }

    public function testAuthorizeMethodThrowsAuthorizationExceptionWithPolicyDenial()
    {
        $user = new User();

        try {
            $user->authorize('willDeny', AuthorizableTestDummy::class);
        } catch (Throwable $e) {
            $this->assertInstanceOf(AuthorizationException::class, $e);
            $this->assertTrue($e->response()->denied());
        }
    }

    public function testAuthorizeMethodReturnsAllowedResponseWithPolicySuccess()
    {
        $user = new User();

        $response = $user->authorize('willSucceed', AuthorizableTestDummy::class);

        $this->assertTrue($response->allowed());
    }
}

class AuthorizableTestDummy
{
}

class AuthorizableTestPolicy
{
    public function willDeny($user)
    {
        return false;
    }

    public function willSucceed($user)
    {
        return true;
    }
}
