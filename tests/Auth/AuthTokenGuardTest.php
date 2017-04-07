<?php

use Illuminate\Http\Request;
use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\UserProvider;

class AuthTokenGuardTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testQueryStringVariableNameIsConfigurable()
    {
        $provider = Mockery::mock(UserProvider::class);
        $request = Request::create('/', 'GET', ['foo' => 'bar']);
        $guard = Mockery::mock(new TokenGuard($provider, $request, ['inputKey' => 'foo']))->shouldAllowMockingProtectedMethods();

        $this->assertEquals('bar', $guard->getTokenForRequest());
    }

    public function testStorageKeyVariableNameIsConfigurable()
    {
        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['foo' => 'bar']);
        $request = Request::create('/', 'GET', ['api_token' => 'bar']);
        $guard = Mockery::mock(new TokenGuard($provider, $request, ['storageKey' => 'foo']));

        $guard->validate(['api_token' => 'bar']);
    }

    public function testUserCanBeRetrievedByQueryStringVariable()
    {
        $provider = Mockery::mock(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'foo'])->andReturn($user);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $user = $guard->user();

        $this->assertEquals(1, $user->id);
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
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'foo'])->andReturn($user);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $this->assertTrue($guard->validate(['api_token' => 'foo']));
    }

    public function testValidateCanDetermineIfCredentialsAreInvalid()
    {
        $provider = Mockery::mock(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'foo'])->andReturn(null);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $this->assertFalse($guard->validate(['api_token' => 'foo']));
    }

    public function testValidateIfApiTokenIsEmpty()
    {
        $provider = Mockery::mock(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $request = Request::create('/', 'GET', ['api_token' => '']);

        $guard = new TokenGuard($provider, $request);

        $this->assertFalse($guard->validate(['api_token' => '']));
    }
}

class AuthTokenGuardTestUser
{
    public $id;

    public function getAuthIdentifier()
    {
        return $this->id;
    }
}
