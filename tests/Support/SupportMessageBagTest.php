<?php

use Illuminate\Support\MessageBag;
use Mockery as m;

class SupportMessageBagTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


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


	public function testMessagesMayBeMerged()
	{
		$container = new MessageBag(array('username' => array('foo')));
		$container->merge(array('username' => array('bar')));
		$this->assertEquals(array('username' => array('foo', 'bar')), $container->getMessages());
	}


	public function testMessageBagsCanBeMerged()
	{
		$container = new MessageBag(array('foo' => array('bar')));
		$otherContainer = new MessageBag(array('foo' => array('baz'), 'bar' => array('foo')));
		$container->merge($otherContainer);
		$this->assertEquals(array('foo' => array('bar', 'baz'), 'bar' => array('foo')), $container->getMessages());
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

		$container->setFormat(':key :message');
		$this->assertEquals('foo bar', $container->first('foo'));
	}


	public function testMessageBagReturnsCorrectArray()
	{
		$container = new MessageBag;
		$container->setFormat(':message');
		$container->add('foo', 'bar');
		$container->add('boom', 'baz');

		$this->assertEquals(array('foo' => array('bar'), 'boom' => array('baz')), $container->toArray());
	}


	public function testMessageBagReturnsExpectedJson()
	{
		$container = new MessageBag;
		$container->setFormat(':message');
		$container->add('foo', 'bar');
		$container->add('boom', 'baz');

		$this->assertEquals('{"foo":["bar"],"boom":["baz"]}', $container->toJson());
	}


	public function testCountReturnsCorrectValue()
	{
		$container = new MessageBag;
		$this->assertEquals(0, $container->count());

		$container->add('foo', 'bar');
		$container->add('foo', 'baz');
		$container->add('boom', 'baz');

		$this->assertEquals(3, $container->count());
	}


	public function testConstructor()
	{
		$messageBag = new MessageBag(array('country' => 'Azerbaijan', 'capital' => 'Baku'));
		$this->assertEquals(array('country' => array('Azerbaijan'), 'capital' => array('Baku')), $messageBag->getMessages());
	}

}
