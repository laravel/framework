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
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['api_token' => 'custom'])->andReturn($user);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);
        $guard->setRequest(Request::create('/', 'GET', ['api_token' => 'custom']));

        $user = $guard->user();

        $this->assertEquals(1, $user->id);
    }

    public function testUserCanBeRetrievedByBearerTokenWithCustomKey()
    {
        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['custom_token_field' => 'foo'])->andReturn((object) ['id' => 1]);
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer foo']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $user = $guard->user();

        $this->assertEquals(1, $user->id);
    }

    public function testUserCanBeRetrievedByQueryStringVariableWithCustomKey()
    {
        $provider = Mockery::mock(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['custom_token_field' => 'foo'])->andReturn($user);
        $request = Request::create('/', 'GET', ['custom_token_field' => 'foo']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $user = $guard->user();

        $this->assertEquals(1, $user->id);
        $this->assertTrue($guard->check());
        $this->assertFalse($guard->guest());
        $this->assertEquals(1, $guard->id());
    }

    public function testUserCanBeRetrievedByAuthHeadersWithCustomField()
    {
        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['custom_token_field' => 'foo'])->andReturn((object) ['id' => 1]);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo', 'PHP_AUTH_PW' => 'foo']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $user = $guard->user();

        $this->assertEquals(1, $user->id);
    }

    public function testValidateCanDetermineIfCredentialsAreValidWithCustomKey()
    {
        $provider = Mockery::mock(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['custom_token_field' => 'foo'])->andReturn($user);
        $request = Request::create('/', 'GET', ['custom_token_field' => 'foo']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $this->assertTrue($guard->validate(['custom_token_field' => 'foo']));
    }

    public function testValidateCanDetermineIfCredentialsAreInvalidWithCustomKey()
    {
        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with(['custom_token_field' => 'foo'])->andReturn(null);
        $request = Request::create('/', 'GET', ['custom_token_field' => 'foo']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $this->assertFalse($guard->validate(['custom_token_field' => 'foo']));
    }

    public function testValidateIfApiTokenIsEmptyWithCustomKey()
    {
        $provider = Mockery::mock(UserProvider::class);
        $request = Request::create('/', 'GET', ['custom_token_field' => '']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $this->assertFalse($guard->validate(['custom_token_field' => '']));
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
