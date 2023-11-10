<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\DatabaseUserProvider;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Connection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class AuthDatabaseUserProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testRetrieveByIDReturnsUserWhenUserIsFound()
    {
        $conn = m::mock(Connection::class);
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn(['id' => 1, 'name' => 'Dayle']);
        $hasher = m::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveById(1);

        $this->assertInstanceOf(GenericUser::class, $user);
        $this->assertSame(1, $user->getAuthIdentifier());
        $this->assertSame('Dayle', $user->name);
    }

    public function testRetrieveByIDReturnsNullWhenUserIsNotFound()
    {
        $conn = m::mock(Connection::class);
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn(null);
        $hasher = m::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveById(1);

        $this->assertNull($user);
    }

    public function testRetrieveByTokenReturnsUser()
    {
        $mockUser = new stdClass;
        $mockUser->remember_token = 'a';

        $conn = m::mock(Connection::class);
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn($mockUser);
        $hasher = m::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertEquals(new GenericUser((array) $mockUser), $user);
    }

    public function testRetrieveTokenWithBadIdentifierReturnsNull()
    {
        $conn = m::mock(Connection::class);
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn(null);
        $hasher = m::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByBadTokenReturnsNull()
    {
        $mockUser = new stdClass;
        $mockUser->remember_token = null;

        $conn = m::mock(Connection::class);
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn($mockUser);
        $hasher = m::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsReturnsUserWhenUserIsFound()
    {
        $conn = m::mock(Connection::class);
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('where')->once()->with('username', 'dayle');
        $conn->shouldReceive('whereIn')->once()->with('group', ['one', 'two']);
        $conn->shouldReceive('first')->once()->andReturn(['id' => 1, 'name' => 'taylor']);
        $hasher = m::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials(['username' => 'dayle', 'password' => 'foo', 'group' => ['one', 'two']]);

        $this->assertInstanceOf(GenericUser::class, $user);
        $this->assertSame(1, $user->getAuthIdentifier());
        $this->assertSame('taylor', $user->name);
    }

    public function testRetrieveByCredentialsAcceptsCallback()
    {
        $conn = m::mock(Connection::class);
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('where')->once()->with('username', 'dayle');
        $conn->shouldReceive('whereIn')->once()->with('group', ['one', 'two']);
        $conn->shouldReceive('first')->once()->andReturn(['id' => 1, 'name' => 'taylor']);
        $hasher = m::mock(Hasher::class);
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
        $conn = m::mock(Connection::class);
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('where')->once()->with('username', 'dayle');
        $conn->shouldReceive('first')->once()->andReturn(null);
        $hasher = m::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials(['username' => 'dayle']);

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsWithMultiplyPasswordsReturnsNull()
    {
        $conn = m::mock(Connection::class);
        $hasher = m::mock(Hasher::class);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials([
            'password' => 'dayle',
            'password2' => 'night',
        ]);

        $this->assertNull($user);
    }

    public function testCredentialValidation()
    {
        $conn = m::mock(Connection::class);
        $hasher = m::mock(Hasher::class);
        $hasher->shouldReceive('check')->once()->with('plain', 'hash')->andReturn(true);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }
}
