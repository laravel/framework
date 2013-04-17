<?php

use Mockery as m;

class SessionStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFlashBagReturnsItemsCorrectly()
	{
		$flash = new Illuminate\Session\FlashBag;
		$data = array('new' => array('name' => array('Taylor')), 'display' => array('foo' => array('bar')));
		$flash->initialize($data);

		$this->assertEquals(array('Taylor'), $flash->get('name'));
	}


	public function testPeekNewReturnsValuesFromNew()
	{
		$flash = new Illuminate\Session\FlashBag;
		$data = array('new' => array('name' => array('Taylor')), 'display' => array('foo' => array('bar')));
		$flash->initialize($data);
		$flash->set('age', 25);

		$this->assertEquals(array(25), $flash->peek('age'));
		$this->assertEquals(array(25), $flash->peekNew('age'));	
		$this->assertTrue($flash->hasNew('age'));
	}

}