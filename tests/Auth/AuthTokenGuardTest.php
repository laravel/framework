<?php

namespace Illuminate\Tests\Auth;

use Mockery;
use Illuminate\Http\Request;
use Illuminate\Auth\TokenGuard;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Auth\UserProvider;

class AuthTokenGuardTest extends TestCase
{
    protected function tearDown()
    {
        Mockery::close();
    }

    public function testUserCanBeRetrievedByQueryStringVariable()
    {
        $provider = Mockery::mock(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $user->api_token = "foo";
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'foo'])->andReturn($user);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $user = $guard->user();

        $this->assertEquals(1, $user->id);
        $this->assertEquals("foo", $user->api_token);
        $this->assertTrue($guard->check());
        $this->assertFalse($guard->guest());
        $this->assertEquals(1, $guard->id());
    }

    public function testUserCanBeRetrievedByAuthHeaders()
    {
        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'foo'])->andReturn((object) ['id' => 1]);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo', 'PHP_AUTH_PW' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $user = $guard->user();

        $this->assertEquals(1, $user->id);
    }

    public function testUserCanBeRetrievedByBearerToken()
    {
        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'foo'])->andReturn((object) ['id' => 1]);
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer foo']);

        $guard = new TokenGuard($provider, $request);

        $user = $guard->user();

        $this->assertEquals(1, $user->id);
    }

    public function testValidateCanDetermineIfCredentialsAreValid()
    {
        $provider = Mockery::mock(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $user->api_token = "foo";
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'foo'])->andReturn($user);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $this->assertTrue($guard->validate(['api_token' => 'foo']));
    }

    public function testValidateCanDetermineIfCredentialsAreInvalid()
    {
        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'foo'])->andReturn(null);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $this->assertFalse($guard->validate(['api_token' => 'foo']));
    }

    public function testValidateIfApiTokenIsEmpty()
    {
        $provider = Mockery::mock(UserProvider::class);
        $request = Request::create('/', 'GET', ['api_token' => '']);

        $guard = new TokenGuard($provider, $request);

        $this->assertFalse($guard->validate(['api_token' => '']));
    }

    public function testItAllowsToPassCustomRequestInSetterAndUseItForValidation()
    {
        $provider = Mockery::mock(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $user->api_token = "foo";
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'custom'])->andReturn($user);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);
        $guard->setRequest(Request::create('/', 'GET', ['api_token' => 'custom']));

        $user = $guard->user();

        $this->assertEquals(1, $user->id);
    }

    public function testItReturnsDifferentUsersWhenTokenIsChangedInSubsequentRequests()
    {
        $provider = Mockery::mock(UserProvider::class);

        $first_user = new AuthTokenGuardTestUser;
        $first_user->id = 1;
        $first_user->api_token = "foo";

        $second_user = new AuthTokenGuardTestUser;
        $second_user->id = 2;
        $second_user->api_token = "bar";

        $provider->shouldReceive('retrieveByCredentials')->twice()->with(['api_token' => 'foo'])->andReturn($first_user);
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'bar'])->andReturn($second_user);

        $request = Request::create('/', 'GET', ['api_token' => 'foo']);
        $guard = new TokenGuard($provider, $request);
        $this->assertEquals(1, $guard->user()->id);

        $guard->setRequest(Request::create('/', 'GET', ['api_token' => 'foo']));
        $this->assertEquals(1, $guard->user()->id);

        $guard->setRequest(Request::create('/', 'GET', ['api_token' => 'bar']));
        $this->assertEquals(2, $guard->user()->id);

        $guard->setRequest(Request::create('/', 'GET', ['api_token' => 'foo']));
        $this->assertEquals(1, $guard->user()->id);
    }
}

class AuthTokenGuardTestUser
{
    public $id;
    public $api_token;

    public function getAuthIdentifier()
    {
        return $this->id;
    }
}
