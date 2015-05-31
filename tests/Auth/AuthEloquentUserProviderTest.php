<?php

use Mockery as m;
use Illuminate\Database\Connection;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthEloquentUserProviderTest extends PHPUnit_Framework_TestCase {

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
		$user = $provider->retrieveByCredentials(array('username' => 'dayle', 'password' => 'foo'));

		$this->assertEquals('bar', $user);
	}


	public function testCredentialValidation()
	{
		$conn = m::mock(Connection::class);
		$hasher = m::mock(Hasher::class);
		$hasher->shouldReceive('check')->once()->with('plain', 'hash')->andReturn(true);
		$provider = new EloquentUserProvider($hasher, 'foo');
		$user = m::mock(Authenticatable::class);
		$user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
		$result = $provider->validateCredentials($user, array('password' => 'plain'));

		$this->assertTrue($result);
	}


	public function testModelsCanBeCreated()
	{
		$conn = m::mock(Connection::class);
		$hasher = m::mock(Hasher::class);
		$provider = new EloquentUserProvider($hasher, 'EloquentProviderUserStub');
		$model = $provider->createModel();

		$this->assertInstanceOf('EloquentProviderUserStub', $model);
	}


	protected function getProviderMock()
	{
		$hasher = m::mock(Hasher::class);
		return $this->getMock(EloquentUserProvider::class, array('createModel'), array($hasher, 'foo'));
	}

}

class EloquentProviderUserStub {}
