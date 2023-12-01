<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class AuthRequestGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testUserDoesntCallRetrieveByCredentialMoreThanOnceWhenGivenAuthentication()
    {
        $provider = m::mock(UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'foo'])->andReturnNull();
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new RequestGuard(function ($request, $provider) {
            return $provider->retrieveByCredentials($request->query());
        }, $request, $provider);

        $this->assertNull($guard->user());

        // Ensure mocked provider.retrieveByCredential expectation only called once.
        $this->assertNull($guard->user());
    }
}
