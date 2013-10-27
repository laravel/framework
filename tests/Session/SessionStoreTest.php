<?php

use Mockery as m;

class SessionStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSessionIsLoadedFromHandler()
	{
		$session = $this->getSession();
		$session->getHandler()->shouldReceive('read')->once()->with(1)->andReturn(serialize(array('foo' => 'bar', 'bagged' => array('name' => 'taylor'))));
		$session->registerBag(new Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag('bagged'));
		$session->start();

		$this->assertEquals('bar', $session->get('foo'));
		$this->assertEquals('baz', $session->get('bar', 'baz'));
		$this->assertTrue($session->has('foo'));
		$this->assertFalse($session->has('bar'));
		$this->assertEquals('taylor', $session->getBag('bagged')->get('name'));
		$this->assertInstanceOf('Symfony\Component\HttpFoundation\Session\Storage\MetadataBag', $session->getMetadataBag());
		$this->assertTrue($session->isStarted());

		$session->put('baz', 'boom');
		$this->assertTrue($session->has('baz'));
	}


	public function testSessionMigration()
	{
		$session = $this->getSession();
		$oldId = $session->getId();
		$session->getHandler()->shouldReceive('destroy')->never();
		$this->assertTrue($session->migrate());
		$this->assertFalse($oldId == $session->getId());


		$session = $this->getSession();
		$oldId = $session->getId();
		$session->getHandler()->shouldReceive('destroy')->once()->with($oldId);
		$this->assertTrue($session->migrate(true));
		$this->assertFalse($oldId == $session->getId());
	}


	public function testSessionIsProperlySaved()
	{
		$session = $this->getSession();
		$session->getHandler()->shouldReceive('read')->once()->andReturn(serialize(array()));
		$session->start();
		$session->put('foo', 'bar');
		$session->flash('baz', 'boom');
		$session->getHandler()->shouldReceive('write')->once()->with(1, serialize(array(
			'_token' => $session->token(),
			'foo' => 'bar',
			'baz' => 'boom',
			'flash' => array(
				'new' => array(),
				'old' => array('baz'),
			),
			'_sf2_meta' => $session->getBagData('_sf2_meta'),
		)));
		$session->save();

		$this->assertFalse($session->isStarted());
	}


	public function testOldInputFlashing()
	{
		$session = $this->getSession();
		$session->put('boom', 'baz');
		$session->flashInput(array('foo' => 'bar'));

		$this->assertTrue($session->hasOldInput('foo'));
		$this->assertEquals('bar', $session->getOldInput('foo'));
		$this->assertFalse($session->hasOldInput('boom'));

		$session->ageFlashData();

		$this->assertTrue($session->hasOldInput('foo'));
		$this->assertEquals('bar', $session->getOldInput('foo'));
		$this->assertFalse($session->hasOldInput('boom'));
	}


	public function testDataFlashing()
	{
		$session = $this->getSession();
		$session->flash('foo', 'bar');

		$this->assertTrue($session->has('foo'));
		$this->assertEquals('bar', $session->get('foo'));

		$session->ageFlashData();

		$this->assertTrue($session->has('foo'));
		$this->assertEquals('bar', $session->get('foo'));

		$session->ageFlashData();

		$this->assertFalse($session->has('foo'));
		$this->assertEquals(null, $session->get('foo'));
	}


	public function getSession()
	{
		$reflection = new ReflectionClass('Illuminate\Session\Store');
		return $reflection->newInstanceArgs($this->getMocks());
	}


	public function getMocks()
	{
		return array(
			'name',
			m::mock('SessionHandlerInterface'),
			1
		);
	}

}