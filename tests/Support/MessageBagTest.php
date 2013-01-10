<?php

use Illuminate\Support\MessageBag;

class MessageBagTest extends PHPUnit_Framework_TestCase {

	public function testUniqueness()
	{
		$container = new MessageBag;
		$container->add('foo', 'bar');
		$container->add('foo', 'bar');
		$messages = $container->getMessages();
		$this->assertEquals(array('bar'), $messages['foo']);
	}


	public function testMessagesAreAdded()
	{
		$container = new MessageBag;
		$container->setFormat(':message');
		$container->add('foo', 'bar');
		$container->add('foo', 'baz');
		$container->add('boom', 'bust');
		$messages = $container->getMessages();
		$this->assertEquals(array('bar', 'baz'), $messages['foo']);
		$this->assertEquals(array('bust'), $messages['boom']);
	}


	public function testGetReturnsArrayOfMessagesByKey()
	{
		$container = new MessageBag;
		$container->setFormat(':message');
		$container->add('foo', 'bar');
		$container->add('foo', 'baz');
		$this->assertEquals(array('bar', 'baz'), $container->get('foo'));
	}


	public function testFirstReturnsSingleMessage()
	{
		$container = new MessageBag;
		$container->setFormat(':message');
		$container->add('foo', 'bar');
		$container->add('foo', 'baz');
		$messages = $container->getMessages();
		$this->assertEquals('bar', $container->first('foo'));
	}


	public function testHasIndicatesExistence()
	{
		$container = new MessageBag;
		$container->setFormat(':message');
		$container->add('foo', 'bar');
		$this->assertTrue($container->has('foo'));
		$this->assertFalse($container->has('bar'));
	}


	public function testAllReturnsAllMessages()
	{
		$container = new MessageBag;
		$container->setFormat(':message');
		$container->add('foo', 'bar');
		$container->add('boom', 'baz');
		$this->assertEquals(array('bar', 'baz'), $container->all());
	}


	public function testFormatIsRespected()
	{
		$container = new MessageBag;
		$container->setFormat('<p>:message</p>');
		$container->add('foo', 'bar');
		$container->add('boom', 'baz');
		$this->assertEquals('<p>bar</p>', $container->first('foo'));
		$this->assertEquals(array('<p>bar</p>'), $container->get('foo'));
		$this->assertEquals(array('<p>bar</p>', '<p>baz</p>'), $container->all());
		$this->assertEquals('bar', $container->first('foo', ':message'));
		$this->assertEquals(array('bar'), $container->get('foo', ':message'));
		$this->assertEquals(array('bar', 'baz'), $container->all(':message'));
	}

}