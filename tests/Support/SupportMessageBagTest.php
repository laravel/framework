<?php

use Illuminate\Support\MessageBag;
use Mockery as m;

class SupportMessageBagTest extends PHPUnit_Framework_TestCase
{
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
        $this->assertEquals(['bar'], $messages['foo']);
    }

    public function testMessagesAreAdded()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $container->add('foo', 'baz');
        $container->add('boom', 'bust');
        $messages = $container->getMessages();
        $this->assertEquals(['bar', 'baz'], $messages['foo']);
        $this->assertEquals(['bust'], $messages['boom']);
    }

    public function testMessagesMayBeMerged()
    {
        $container = new MessageBag(['username' => ['foo']]);
        $container->merge(['username' => ['bar']]);
        $this->assertEquals(['username' => ['foo', 'bar']], $container->getMessages());
    }

    public function testMessageBagsCanBeMerged()
    {
        $container = new MessageBag(['foo' => ['bar']]);
        $otherContainer = new MessageBag(['foo' => ['baz'], 'bar' => ['foo']]);
        $container->merge($otherContainer);
        $this->assertEquals(['foo' => ['bar', 'baz'], 'bar' => ['foo']], $container->getMessages());
    }

    public function testGetReturnsArrayOfMessagesByKey()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $container->add('foo', 'baz');
        $this->assertEquals(['bar', 'baz'], $container->get('foo'));
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
        $this->assertEquals(['bar', 'baz'], $container->all());
    }

    public function testFormatIsRespected()
    {
        $container = new MessageBag;
        $container->setFormat('<p>:message</p>');
        $container->add('foo', 'bar');
        $container->add('boom', 'baz');
        $this->assertEquals('<p>bar</p>', $container->first('foo'));
        $this->assertEquals(['<p>bar</p>'], $container->get('foo'));
        $this->assertEquals(['<p>bar</p>', '<p>baz</p>'], $container->all());
        $this->assertEquals('bar', $container->first('foo', ':message'));
        $this->assertEquals(['bar'], $container->get('foo', ':message'));
        $this->assertEquals(['bar', 'baz'], $container->all(':message'));

        $container->setFormat(':key :message');
        $this->assertEquals('foo bar', $container->first('foo'));
    }

    public function testMessageBagReturnsCorrectArray()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $container->add('boom', 'baz');

        $this->assertEquals(['foo' => ['bar'], 'boom' => ['baz']], $container->toArray());
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
        $this->assertCount(0, $container);

        $container->add('foo', 'bar');
        $container->add('foo', 'baz');
        $container->add('boom', 'baz');

        $this->assertCount(3, $container);
    }

    public function testCountable()
    {
        $container = new MessageBag;

        $container->add('foo', 'bar');
        $container->add('boom', 'baz');

        $this->assertCount(2, $container);
    }

    public function testConstructor()
    {
        $messageBag = new MessageBag(['country' => 'Azerbaijan', 'capital' => 'Baku']);
        $this->assertEquals(['country' => ['Azerbaijan'], 'capital' => ['Baku']], $messageBag->getMessages());
    }
}
