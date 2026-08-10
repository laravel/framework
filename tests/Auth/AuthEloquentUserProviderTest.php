<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

class AuthEloquentUserProviderTest extends TestCase
{
    public function testRetrieveByIDReturnsUser()
    {
        $provider = $this->getProviderMock();
        $mock = Mockery::mock(stdClass::class);
        $mock->expects('newQuery')->andReturn($mock);
        $mock->expects('getAuthIdentifierName')->andReturn('id');
        $mock->expects('where')->with('id', 1)->andReturn($mock);
        $mock->expects('first')->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $user = $provider->retrieveById(1);

        $this->assertSame('bar', $user);
    }

    public function testRetrieveByTokenReturnsUser()
    {
        $mockUser = Mockery::mock(Authenticatable::class);
        $mockUser->expects('getRememberToken')->andReturn('a');

        $provider = $this->getProviderMock();
        $mock = Mockery::mock(stdClass::class);
        $mock->expects('newQuery')->andReturn($mock);
        $mock->expects('getAuthIdentifierName')->andReturn('id');
        $mock->expects('where')->with('id', 1)->andReturn($mock);
        $mock->expects('first')->andReturn($mockUser);
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertEquals($mockUser, $user);
    }

    public function testRetrieveTokenWithBadIdentifierReturnsNull()
    {
        $provider = $this->getProviderMock();
        $mock = Mockery::mock(stdClass::class);
        $mock->expects('newQuery')->andReturn($mock);
        $mock->expects('getAuthIdentifierName')->andReturn('id');
        $mock->expects('where')->with('id', 1)->andReturn($mock);
        $mock->expects('first')->andReturn(null);
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrievingWithOnlyPasswordCredentialReturnsNull()
    {
        $provider = $this->getProviderMock();
        $user = $provider->retrieveByCredentials(['api_password' => 'foo']);

        $this->assertNull($user);
    }

    public function testRetrieveByBadTokenReturnsNull()
    {
        $mockUser = Mockery::mock(Authenticatable::class);
        $mockUser->expects('getRememberToken')->andReturn(null);

        $provider = $this->getProviderMock();
        $mock = Mockery::mock(stdClass::class);
        $mock->expects('newQuery')->andReturn($mock);
        $mock->expects('getAuthIdentifierName')->andReturn('id');
        $mock->expects('where')->with('id', 1)->andReturn($mock);
        $mock->expects('first')->andReturn($mockUser);
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsReturnsUser()
    {
        $provider = $this->getProviderMock();
        $mock = Mockery::mock(stdClass::class);
        $mock->expects('newQuery')->andReturn($mock);
        $mock->expects('where')->with('username', 'dayle');
        $mock->expects('whereIn')->with('group', ['one', 'two']);
        $mock->expects('first')->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $user = $provider->retrieveByCredentials(['username' => 'dayle', 'password' => 'foo', 'group' => ['one', 'two']]);

        $this->assertSame('bar', $user);
    }

    public function testRetrieveByCredentialsAcceptsCallback()
    {
        $provider = $this->getProviderMock();
        $mock = Mockery::mock(stdClass::class);
        $mock->expects('newQuery')->andReturn($mock);
        $mock->expects('where')->with('username', 'dayle');
        $mock->expects('whereIn')->with('group', ['one', 'two']);
        $mock->expects('first')->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $user = $provider->retrieveByCredentials([function ($builder) {
            $builder->where('username', 'dayle');
            $builder->whereIn('group', ['one', 'two']);
        }]);

        $this->assertSame('bar', $user);
    }

    public function testRetrieveByCredentialsWithMultiplyPasswordsReturnsNull()
    {
        $provider = $this->getProviderMock();
        $user = $provider->retrieveByCredentials([
            'password' => 'dayle',
            'password2' => 'night',
        ]);

        $this->assertNull($user);
    }

    public function testCredentialValidation()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('check')->with('plain', 'hash')->andReturn(true);
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }

    public function testCredentialValidationFailed()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('check')->with('plain', 'hash')->andReturn(false);
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertFalse($result);
    }

    public function testCredentialValidationFailsGracefullyWithNullPassword()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('check')->never();
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn(null);
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertFalse($result);
    }

    public function testRehashPasswordIfRequired()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('needsRehash')->with('hash')->andReturn(true);
        $hasher->expects('make')->with('plain')->andReturn('rehashed');

        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $user->expects('getAuthPasswordName')->andReturn('password_attribute');
        $user->expects('forceFill')->with(['password_attribute' => 'rehashed'])->andReturnSelf();
        $user->expects('save');

        $provider = new EloquentUserProvider($hasher, 'foo');
        $provider->rehashPasswordIfRequired($user, ['password' => 'plain']);
    }

    public function testDontRehashPasswordIfNotRequired()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('needsRehash')->with('hash')->andReturn(false);
        $hasher->shouldNotReceive('make');

        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $user->shouldNotReceive('getAuthPasswordName');
        $user->shouldNotReceive('forceFill');
        $user->shouldNotReceive('save');

        $provider = new EloquentUserProvider($hasher, 'foo');
        $provider->rehashPasswordIfRequired($user, ['password' => 'plain']);
    }

    public function testModelsCanBeCreated()
    {
        $hasher = Mockery::mock(Hasher::class);
        $provider = new EloquentUserProvider($hasher, EloquentProviderUserStub::class);
        $model = $provider->createModel();

        $this->assertInstanceOf(EloquentProviderUserStub::class, $model);
    }

    public function testRegistersQueryHandler()
    {
        $callback = function ($builder) {
            $builder->whereIn('group', ['one', 'two']);
        };

        $provider = $this->getProviderMock();
        $mock = Mockery::mock(stdClass::class);
        $mock->expects('newQuery')->andReturn($mock);
        $mock->expects('where')->with('username', 'dayle');
        $mock->expects('whereIn')->with('group', ['one', 'two']);
        $mock->expects('first')->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $provider->withQuery($callback);
        $user = $provider->retrieveByCredentials([function ($builder) {
            $builder->where('username', 'dayle');
        }]);

        $this->assertSame('bar', $user);
        $this->assertSame($callback, $provider->getQueryCallback());
    }

    protected function getProviderMock()
    {
        $hasher = Mockery::mock(Hasher::class);

        return $this->getMockBuilder(EloquentUserProvider::class)->onlyMethods(['createModel'])->setConstructorArgs([$hasher, 'foo'])->getMock();
    }
}

class EloquentProviderUserStub
{
    //
}
