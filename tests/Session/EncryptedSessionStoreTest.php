<?php

use Mockery as m;

class EncryptedSessionStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSessionIsProperlyEncrypted()
	{
		$session = $this->getSession();
		$session->getEncrypter()->shouldReceive('decrypt')->once()->with(json_encode([]))->andReturn(json_encode([]));
		$session->getHandler()->shouldReceive('read')->once()->andReturn(json_encode(array()));
		$session->start();
		$session->put('foo', 'bar');
		$session->flash('baz', 'boom');
		$serialized = json_encode(array(
			'_token' => $session->token(),
			'foo' => 'bar',
			'baz' => 'boom',
			'flash' => array(
				'new' => array(),
				'old' => array('baz'),
			),
			'_sf2_meta' => $session->getBagData('_sf2_meta'),
		));
		$session->getEncrypter()->shouldReceive('encrypt')->once()->with($serialized)->andReturn($serialized);
		$session->getHandler()->shouldReceive('write')->once()->with(
			$this->getSessionId(),
			$serialized
		);
		$session->save();

		$this->assertFalse($session->isStarted());
	}


	public function getSession()
	{
		$reflection = new ReflectionClass('Illuminate\Session\EncryptedStore');
		return $reflection->newInstanceArgs($this->getMocks());
	}


	public function getMocks()
	{
		return array(
			$this->getSessionName(),
			m::mock('SessionHandlerInterface'),
			m::mock('Illuminate\Contracts\Encryption\Encrypter'),
			$this->getSessionId(),
		);
	}


	public function getSessionId()
	{
		return 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
	}


	public function getSessionName()
	{
		return 'name';
	}

}
