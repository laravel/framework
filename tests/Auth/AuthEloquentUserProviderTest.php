<?php

namespace Illuminate\Tests\Auth;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\EloquentUserProvider;

class AuthEloquentUserProviderTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRetrieveByIDReturnsUser()
    {
        $provider = $this->getProviderMock();
        $mock = m::mock('stdClass');
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $mock->shouldReceive('where')->once()->with('id', 1)->andReturn($mock);
        $mock->shouldReceive('first')->once()->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->will($this->returnValue($mock));
        $user = $provider->retrieveById(1);

        $this->assertEquals('bar', $user);
    }

    public function testRetrieveByTokenReturnsUser()
    {
        $mockUser = m::mock('stdClass');
        $mockUser->shouldReceive('getRememberToken')->once()->andReturn('a');

        $provider = $this->getProviderMock();
        $mock = m::mock('stdClass');
        $mock->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $mock->shouldReceive('where')->once()->with('id', 1)->andReturn($mock);
        $mock->shouldReceive('first')->once()->andReturn($mockUser);
        $provider->expects($this->once())->method('createModel')->will($this->returnValue($mock));
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertEquals($mockUser, $user);
    }

    public function testRetrieveTokenWithBadIdentifierReturnsNull()
    {
        $provider = $this->getProviderMock();
        $mock = m::mock('stdClass');
        $mock->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $mock->shouldReceive('where')->once()->with('id', 1)->andReturn($mock);
        $mock->shouldReceive('first')->once()->andReturn(null);
        $provider->expects($this->once())->method('createModel')->will($this->returnValue($mock));
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByBadTokenReturnsNull()
    {
        $mockUser = m::mock('stdClass');
        $mockUser->shouldReceive('getRememberToken')->once()->andReturn(null);

        $provider = $this->getProviderMock();
        $mock = m::mock('stdClass');
        $mock->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $mock->shouldReceive('where')->once()->with('id', 1)->andReturn($mock);
        $mock->shouldReceive('first')->once()->andReturn($mockUser);
        $provider->expects($this->once())->method('createModel')->will($this->returnValue($mock));
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsReturnsUser()
    {
        $provider = $this->getProviderMock();
        $mock = m::mock('stdClass');
        $mock->shouldReceive('newQuery')->once()->andReturn($mock);
        $mock->shouldReceive('where')->once()->with('username', 'dayle');
        $mock->shouldReceive('first')->once()->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->will($this->returnValue($mock));
        $user = $provider->retrieveByCredentials(['username' => 'dayle', 'password' => 'foo']);

        $this->assertEquals('bar', $user);
    }

    public function testCredentialValidation()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $hasher->shouldReceive('check')->once()->with('plain', 'hash')->andReturn(true);
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }

    public function testModelsCanBeCreated()
    {
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new EloquentUserProvider($hasher, 'Illuminate\Tests\Auth\EloquentProviderUserStub');
        $model = $provider->createModel();

        $this->assertInstanceOf('Illuminate\Tests\Auth\EloquentProviderUserStub', $model);
    }

    protected function getProviderMock()
    {
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');

        return $this->getMockBuilder('Illuminate\Auth\EloquentUserProvider')->setMethods(['createModel'])->setConstructorArgs([$hasher, 'foo'])->getMock();
    }
}

class EloquentProviderUserStub
{
}
