<?php

use Mockery as m;

class AuthEloquentUserProviderTest extends PHPUnit_Framework_TestCase
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
        $mock->shouldReceive('find')->once()->with(1)->andReturn('bar');
        $provider->expects($this->once())->method('createModel')->will($this->returnValue($mock));
        $user = $provider->retrieveById(1);

        $this->assertEquals('bar', $user);
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
        $provider = new Illuminate\Auth\EloquentUserProvider($hasher, 'foo');
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }

    public function testModelsCanBeCreated()
    {
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');
        $provider = new Illuminate\Auth\EloquentUserProvider($hasher, 'EloquentProviderUserStub');
        $model = $provider->createModel();

        $this->assertInstanceOf('EloquentProviderUserStub', $model);
    }

    protected function getProviderMock()
    {
        $hasher = m::mock('Illuminate\Contracts\Hashing\Hasher');

        return $this->getMock('Illuminate\Auth\EloquentUserProvider', ['createModel'], [$hasher, 'foo']);
    }
}

class EloquentProviderUserStub
{
}
