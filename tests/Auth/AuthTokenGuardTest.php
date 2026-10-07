<?php

namespace Illuminate\Tests\Auth;

use JMac\Testing\Double;
use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\TestCase;

class AuthTokenGuardTest extends TestCase
{
    public function testUserCanBeRetrievedByQueryStringVariable()
    {
        $provider = Double::for(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $provider->expects('retrieveByCredentials')->with(['api_token' => 'foo'])->andReturn($user);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $user = $guard->user();

        $this->assertSame(1, $user->id);
        $this->assertTrue($guard->check());
        $this->assertFalse($guard->guest());
        $this->assertSame(1, $guard->id());
    }

    public function testTokenCanBeHashed()
    {
        $provider = Double::for(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $provider->expects('retrieveByCredentials')->with(['api_token' => hash('sha256', 'foo')])->andReturn($user);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request, 'api_token', 'api_token', $hash = true);

        $user = $guard->user();

        $this->assertSame(1, $user->id);
        $this->assertTrue($guard->check());
        $this->assertFalse($guard->guest());
        $this->assertSame(1, $guard->id());
    }

    public function testUserCannotBeRetrievedWithNonStringToken()
    {
        $provider = Double::for(UserProvider::class);
        $provider->shouldNotReceive('retrieveByCredentials');
        $request = Request::create('/', 'GET', ['api_token' => [0]]);

        $guard = new TokenGuard($provider, $request);

        $this->assertNull($guard->user());
    }

    public function testUserCanBeRetrievedByAuthHeaders()
    {
        $provider = Double::for(UserProvider::class);
        $provider->expects('retrieveByCredentials')->with(['api_token' => 'foo'])->andReturn((object) ['id' => 1]);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo', 'PHP_AUTH_PW' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $user = $guard->user();

        $this->assertSame(1, $user->id);
    }

    public function testUserCanBeRetrievedByBearerToken()
    {
        $provider = Double::for(UserProvider::class);
        $provider->expects('retrieveByCredentials')->with(['api_token' => 'foo'])->andReturn((object) ['id' => 1]);
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer foo']);

        $guard = new TokenGuard($provider, $request);

        $user = $guard->user();

        $this->assertSame(1, $user->id);
    }

    public function testValidateCanDetermineIfCredentialsAreValid()
    {
        $provider = Double::for(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $provider->expects('retrieveByCredentials')->with(['api_token' => 'foo'])->andReturn($user);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $this->assertTrue($guard->validate(['api_token' => 'foo']));
    }

    public function testValidateCanDetermineIfCredentialsAreInvalid()
    {
        $provider = Double::for(UserProvider::class);
        $provider->expects('retrieveByCredentials')->with(['api_token' => 'foo'])->andReturn(null);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);

        $this->assertFalse($guard->validate(['api_token' => 'foo']));
    }

    public function testValidateIfApiTokenIsEmpty()
    {
        $provider = Double::for(UserProvider::class);
        $request = Request::create('/', 'GET', ['api_token' => '']);

        $guard = new TokenGuard($provider, $request);

        $this->assertFalse($guard->validate(['api_token' => '']));
    }

    public function testValidateRejectsNonStringToken()
    {
        $provider = Double::for(UserProvider::class);
        $provider->shouldNotReceive('retrieveByCredentials');
        $request = Request::create('/');

        $guard = new TokenGuard($provider, $request);

        $this->assertFalse($guard->validate(['api_token' => [0]]));
    }

    public function testItAllowsToPassCustomRequestInSetterAndUseItForValidation()
    {
        $provider = Double::for(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $provider->expects('retrieveByCredentials')->with(['api_token' => 'custom'])->andReturn($user);
        $request = Request::create('/', 'GET', ['api_token' => 'foo']);

        $guard = new TokenGuard($provider, $request);
        $guard->setRequest(Request::create('/', 'GET', ['api_token' => 'custom']));

        $user = $guard->user();

        $this->assertSame(1, $user->id);
    }

    public function testUserCanBeRetrievedByBearerTokenWithCustomKey()
    {
        $provider = Double::for(UserProvider::class);
        $provider->expects('retrieveByCredentials')->with(['custom_token_field' => 'foo'])->andReturn((object) ['id' => 1]);
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer foo']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $user = $guard->user();

        $this->assertSame(1, $user->id);
    }

    public function testUserCanBeRetrievedByQueryStringVariableWithCustomKey()
    {
        $provider = Double::for(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $provider->expects('retrieveByCredentials')->with(['custom_token_field' => 'foo'])->andReturn($user);
        $request = Request::create('/', 'GET', ['custom_token_field' => 'foo']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $user = $guard->user();

        $this->assertSame(1, $user->id);
        $this->assertTrue($guard->check());
        $this->assertFalse($guard->guest());
        $this->assertSame(1, $guard->id());
    }

    public function testUserCanBeRetrievedByAuthHeadersWithCustomField()
    {
        $provider = Double::for(UserProvider::class);
        $provider->expects('retrieveByCredentials')->with(['custom_token_field' => 'foo'])->andReturn((object) ['id' => 1]);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo', 'PHP_AUTH_PW' => 'foo']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $user = $guard->user();

        $this->assertSame(1, $user->id);
    }

    public function testValidateCanDetermineIfCredentialsAreValidWithCustomKey()
    {
        $provider = Double::for(UserProvider::class);
        $user = new AuthTokenGuardTestUser;
        $user->id = 1;
        $provider->expects('retrieveByCredentials')->with(['custom_token_field' => 'foo'])->andReturn($user);
        $request = Request::create('/', 'GET', ['custom_token_field' => 'foo']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $this->assertTrue($guard->validate(['custom_token_field' => 'foo']));
    }

    public function testValidateCanDetermineIfCredentialsAreInvalidWithCustomKey()
    {
        $provider = Double::for(UserProvider::class);
        $provider->expects('retrieveByCredentials')->with(['custom_token_field' => 'foo'])->andReturn(null);
        $request = Request::create('/', 'GET', ['custom_token_field' => 'foo']);

        $guard = new TokenGuard($provider, $request, 'custom_token_field', 'custom_token_field');

        $this->assertFalse($guard->validate(['custom_token_field' => 'foo']));
    }

    public function testValidateIfApiTokenIsEmptyWithCustomKey()
    {
        $provider = Double::for(UserProvider::class);
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
