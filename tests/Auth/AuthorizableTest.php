<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Gate;
use Illuminate\Container\Container;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\TestCase;
use Orchestra\Testbench\Concerns\CreatesApplication;
use Throwable;

//class AuthorizableTest extends TestCase
//{
//    use CreatesApplication;
//
//    public function testAuthorizeMethodThrowsAuthorizationExceptionWithPolicyDenial()
//    {
//        \Illuminate\Support\Facades\Gate::policy(AuthorizableTestDummy::class, AuthorizableTestPolicy::class);
//
//        $user = new User();
//
//        try {
//            $user->authorize('willDeny', AuthorizableTestDummy::class);
//        } catch (Throwable $e) {
//            $this->assertInstanceOf(AuthorizationException::class, $e);
//            $this->assertTrue($e->response()->denied());
//        }
//    }
//
//    public function testAuthorizeMethodReturnsAllowedResponseWithPolicySuccess()
//    {
//        \Illuminate\Support\Facades\Gate::policy(AuthorizableTestDummy::class, AuthorizableTestPolicy::class);
//
//        $user = new User();
//
//        $response = $user->authorize('willSucceed', AuthorizableTestDummy::class);
//
//        $this->assertTrue($response->allowed());
//    }
//}
//
//class AuthorizableTestDummy
//{
//
//}
//
//class AuthorizableTestPolicy
//{
//    public function willDeny($user)
//    {
//        return false;
//    }
//
//    public function willSucceed($user)
//    {
//        return true;
//    }
//}
