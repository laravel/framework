<?php

namespace Illuminate\Tests\Auth;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\GenericUser;
use Illuminate\Auth\DatabaseUserProvider;

class AuthDatabaseUserProviderTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRetrieveByIDReturnsUserWhenUserIsFound()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn(['id' => 1, 'name' => 'Dayle']);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveById(1);

        $this->assertInstanceOf('Illuminate\Auth\GenericUser', $user);
        $this->assertEquals(1, $user->getAuthIdentifier());
        $this->assertEquals('Dayle', $user->name);
    }

    public function testRetrieveByIDReturnsNullWhenUserIsNotFound()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn(null);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveById(1);

        $this->assertNull($user);
    }

    public function testRetrieveByTokenReturnsUser()
    {
        $mockUser = new \stdClass();
        $mockUser->remember_token = 'a';

        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn($mockUser);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertEquals(new GenericUser((array) $mockUser), $user);
    }

    public function testRetrieveTokenWithBadIdentifierReturnsNull()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn(null);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByBadTokenReturnsNull()
    {
        $mockUser = new \stdClass();
        $mockUser->remember_token = null;

        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('find')->once()->with(1)->andReturn($mockUser);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsReturnsUserWhenUserIsFound()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('where')->once()->with('username', 'dayle');
        $conn->shouldReceive('first')->once()->andReturn(['id' => 1, 'name' => 'taylor']);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials(['username' => 'dayle', 'password' => 'foo']);

        $this->assertInstanceOf('Illuminate\Auth\GenericUser', $user);
        $this->assertEquals(1, $user->getAuthIdentifier());
        $this->assertEquals('taylor', $user->name);
    }

    public function testRetrieveByCredentialsReturnsNullWhenUserIsFound()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('table')->once()->with('foo')->andReturn($conn);
        $conn->shouldReceive('where')->once()->with('username', 'dayle');
        $conn->shouldReceive('first')->once()->andReturn(null);
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials(['username' => 'dayle']);

        $this->assertNull($user);
    }

    public function testCredentialValidation()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $hasher->shouldReceive('check')->once()->with('plain', 'hash')->andReturn(true);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }
}
