<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\DatabaseUserProvider;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

class AuthDatabaseUserProviderTest extends TestCase
{
    public function testRetrieveByIDReturnsUserWhenUserIsFound()
    {
        $conn = Mockery::mock(Connection::class);
        $conn->expects('table')->with('foo')->andReturn($conn);
        $conn->expects('find')->with(1)->andReturn(['id' => 1, 'name' => 'Dayle']);
        $hasher = Mockery::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveById(1);

        $this->assertInstanceOf(GenericUser::class, $user);
        $this->assertSame(1, $user->getAuthIdentifier());
        $this->assertSame('Dayle', $user->name);
    }

    public function testRetrieveByIDReturnsNullWhenUserIsNotFound()
    {
        $conn = Mockery::mock(Connection::class);
        $conn->expects('table')->with('foo')->andReturn($conn);
        $conn->expects('find')->with(1)->andReturn(null);
        $hasher = Mockery::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveById(1);

        $this->assertNull($user);
    }

    public function testRetrieveByTokenReturnsUser()
    {
        $mockUser = new stdClass;
        $mockUser->remember_token = 'a';

        $conn = Mockery::mock(Connection::class);
        $conn->expects('table')->with('foo')->andReturn($conn);
        $conn->expects('find')->with(1)->andReturn($mockUser);
        $hasher = Mockery::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertEquals(new GenericUser((array) $mockUser), $user);
    }

    public function testRetrieveTokenWithBadIdentifierReturnsNull()
    {
        $conn = Mockery::mock(Connection::class);
        $conn->expects('table')->with('foo')->andReturn($conn);
        $conn->expects('find')->with(1)->andReturn(null);
        $hasher = Mockery::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByBadTokenReturnsNull()
    {
        $mockUser = new stdClass;
        $mockUser->remember_token = null;

        $conn = Mockery::mock(Connection::class);
        $conn->expects('table')->with('foo')->andReturn($conn);
        $conn->expects('find')->with(1)->andReturn($mockUser);
        $hasher = Mockery::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsReturnsUserWhenUserIsFound()
    {
        $conn = Mockery::mock(Connection::class);
        $conn->expects('table')->with('foo')->andReturn($conn);
        $conn->expects('where')->with('username', 'dayle');
        $conn->expects('whereIn')->with('group', ['one', 'two']);
        $conn->expects('first')->andReturn(['id' => 1, 'name' => 'taylor']);
        $hasher = Mockery::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials(['username' => 'dayle', 'password' => 'foo', 'group' => ['one', 'two']]);

        $this->assertInstanceOf(GenericUser::class, $user);
        $this->assertSame(1, $user->getAuthIdentifier());
        $this->assertSame('taylor', $user->name);
    }

    public function testRetrieveByCredentialsAcceptsCallback()
    {
        $conn = Mockery::mock(Connection::class);
        $conn->expects('table')->with('foo')->andReturn($conn);
        $conn->expects('where')->with('username', 'dayle');
        $conn->expects('whereIn')->with('group', ['one', 'two']);
        $conn->expects('first')->andReturn(['id' => 1, 'name' => 'taylor']);
        $hasher = Mockery::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');

        $user = $provider->retrieveByCredentials([function ($builder) {
            $builder->where('username', 'dayle');
            $builder->whereIn('group', ['one', 'two']);
        }]);

        $this->assertInstanceOf(GenericUser::class, $user);
        $this->assertSame(1, $user->getAuthIdentifier());
        $this->assertSame('taylor', $user->name);
    }

    public function testRetrieveByCredentialsReturnsNullWhenUserIsFound()
    {
        $conn = Mockery::mock(Connection::class);
        $conn->expects('table')->with('foo')->andReturn($conn);
        $conn->expects('where')->with('username', 'dayle');
        $conn->expects('first')->andReturn(null);
        $hasher = Mockery::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials(['username' => 'dayle']);

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsWithMultiplyPasswordsReturnsNull()
    {
        $conn = Mockery::mock(Connection::class);
        $hasher = Mockery::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials([
            'password' => 'dayle',
            'password2' => 'night',
        ]);

        $this->assertNull($user);
    }

    public function testCredentialValidation()
    {
        $conn = Mockery::mock(Connection::class);
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('check')->with('plain', 'hash')->andReturn(true);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }

    public function testCredentialValidationFails()
    {
        $conn = Mockery::mock(Connection::class);
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('check')->with('plain', 'hash')->andReturn(false);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertFalse($result);
    }

    public function testCredentialValidationFailsGracefullyWithNullPassword()
    {
        $conn = Mockery::mock(Connection::class);
        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('check')->never();
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
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

        $conn = Mockery::mock(Connection::class);
        $table = Mockery::mock(ConnectionInterface::class);
        $conn->expects('table')->with('foo')->andReturn($table);
        $table->expects('where')->with('id', 1)->andReturnSelf();
        $table->expects('update')->with(['password_attribute' => 'rehashed']);

        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthIdentifierName')->andReturn('id');
        $user->expects('getAuthIdentifier')->andReturn(1);
        $user->expects('getAuthPassword')->andReturn('hash');
        $user->expects('getAuthPasswordName')->andReturn('password_attribute');

        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $provider->rehashPasswordIfRequired($user, ['password' => 'plain']);
    }

    public function testDontRehashPasswordIfNotRequired()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('needsRehash')->with('hash')->andReturn(false);
        $hasher->shouldNotReceive('make');

        $conn = Mockery::mock(Connection::class);
        $table = Mockery::mock(ConnectionInterface::class);
        $conn->shouldNotReceive('table');
        $table->shouldNotReceive('where');
        $table->shouldNotReceive('update');

        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $user->shouldNotReceive('getAuthIdentifierName');
        $user->shouldNotReceive('getAuthIdentifier');
        $user->shouldNotReceive('getAuthPasswordName');

        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $provider->rehashPasswordIfRequired($user, ['password' => 'plain']);
    }
}
