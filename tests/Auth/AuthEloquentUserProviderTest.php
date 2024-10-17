<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class AuthEloquentUserProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testRetrieveByIDReturnsUser()
    {
        $provider = $this->getProviderMock();
        $mock = m::mock(stdClass::class);
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $mock->shouldReceive('where')->once()->with('id', 1)->andReturn($mock);
        $mock->shouldReceive('first')->once()->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $user = $provider->retrieveById(1);

        $this->assertSame('bar', $user);
    }

    public function testRetrieveByTokenReturnsUser()
    {
        $mockUser = m::mock(stdClass::class);
        $mockUser->shouldReceive('getRememberToken')->once()->andReturn('a');

        $provider = $this->getProviderMock();
        $mock = m::mock(stdClass::class);
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $mock->shouldReceive('where')->once()->with('id', 1)->andReturn($mock);
        $mock->shouldReceive('first')->once()->andReturn($mockUser);
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertEquals($mockUser, $user);
    }

    public function testRetrieveTokenWithBadIdentifierReturnsNull()
    {
        $provider = $this->getProviderMock();
        $mock = m::mock(stdClass::class);
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $mock->shouldReceive('where')->once()->with('id', 1)->andReturn($mock);
        $mock->shouldReceive('first')->once()->andReturn(null);
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
        $mockUser = m::mock(stdClass::class);
        $mockUser->shouldReceive('getRememberToken')->once()->andReturn(null);

        $provider = $this->getProviderMock();
        $mock = m::mock(stdClass::class);
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $mock->shouldReceive('where')->once()->with('id', 1)->andReturn($mock);
        $mock->shouldReceive('first')->once()->andReturn($mockUser);
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsReturnsUser()
    {
        $provider = $this->getProviderMock();
        $mock = m::mock(stdClass::class);
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('where')->once()->with('username', 'dayle');
        $mock->shouldReceive('whereIn')->once()->with('group', ['one', 'two']);
        $mock->shouldReceive('first')->once()->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->willReturn($mock);
        $user = $provider->retrieveByCredentials(['username' => 'dayle', 'password' => 'foo', 'group' => ['one', 'two']]);

        $this->assertSame('bar', $user);
    }

    public function testRetrieveByCredentialsAcceptsCallback()
    {
        $provider = $this->getProviderMock();
        $mock = m::mock(stdClass::class);
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('where')->once()->with('username', 'dayle');
        $mock->shouldReceive('whereIn')->once()->with('group', ['one', 'two']);
        $mock->shouldReceive('first')->once()->andReturn('bar');
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
        $hasher = m::mock(Hasher::class);
        $hasher->shouldReceive('check')->once()->with('plain', 'hash')->andReturn(true);
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }

    public function testCredentialValidationFailed()
    {
        $hasher = m::mock(Hasher::class);
        $hasher->shouldReceive('check')->once()->with('plain', 'hash')->andReturn(false);
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertFalse($result);
    }

    public function testCredentialValidationFailsGracefullyWithNullPassword()
    {
        $hasher = m::mock(Hasher::class);
        $hasher->shouldReceive('check')->never();
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthPassword')->once()->andReturn(null);
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertFalse($result);
    }

    public function testRehashPasswordIfRequired()
    {
        $hasher = m::mock(Hasher::class);
        $hasher->shouldReceive('needsRehash')->once()->with('hash')->andReturn(true);
        $hasher->shouldReceive('make')->once()->with('plain')->andReturn('rehashed');

        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
        $user->shouldReceive('getAuthPasswordName')->once()->andReturn('password_attribute');
        $user->shouldReceive('forceFill')->once()->with(['password_attribute' => 'rehashed'])->andReturnSelf();
        $user->shouldReceive('save')->once();

        $provider = new EloquentUserProvider($hasher, 'foo');
        $provider->rehashPasswordIfRequired($user, ['password' => 'plain']);
    }

    public function testDontRehashPasswordIfNotRequired()
    {
        $hasher = m::mock(Hasher::class);
        $hasher->shouldReceive('needsRehash')->once()->with('hash')->andReturn(false);
        $hasher->shouldNotReceive('make');

        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
        $user->shouldNotReceive('getAuthPasswordName');
        $user->shouldNotReceive('forceFill');
        $user->shouldNotReceive('save');

        $provider = new EloquentUserProvider($hasher, 'foo');
        $provider->rehashPasswordIfRequired($user, ['password' => 'plain']);
    }

    public function testModelsCanBeCreated()
    {
        $hasher = m::mock(Hasher::class);
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
        $mock = m::mock(stdClass::class);
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('where')->once()->with('username', 'dayle');
        $mock->shouldReceive('whereIn')->once()->with('group', ['one', 'two']);
        $mock->shouldReceive('first')->once()->andReturn('bar');
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
        $hasher = m::mock(Hasher::class);

        return $this->getMockBuilder(EloquentUserProvider::class)->onlyMethods(['createModel'])->setConstructorArgs([$hasher, 'foo'])->getMock();
    }
}

class EloquentProviderUserStub
{
    //
}
