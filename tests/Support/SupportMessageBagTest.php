<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\TestCase;

class SupportMessageBagTest extends TestCase
{
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

    public function testKeys()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $container->add('foo', 'baz');
        $container->add('boom', 'bust');
        $this->assertEquals(['foo', 'boom'], $container->keys());
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

    public function testMessageBagsCanConvertToArrays()
    {
        $container = new MessageBag([
            Collection::make(['foo', 'bar']),
            Collection::make(['baz', 'qux']),
        ]);
        $this->assertSame([['foo', 'bar'], ['baz', 'qux']], $container->getMessages());
    }

    public function testGetReturnsArrayOfMessagesByKey()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $container->add('foo', 'baz');
        $this->assertEquals(['bar', 'baz'], $container->get('foo'));
    }

    public function testGetReturnsArrayOfMessagesByImplicitKey()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo.1', 'bar');
        $container->add('foo.2', 'baz');
        $this->assertEquals(['foo.1' => ['bar'], 'foo.2' => ['baz']], $container->get('foo.*'));
    }

    public function testFirstReturnsSingleMessage()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $container->add('foo', 'baz');
        $this->assertSame('bar', $container->first('foo'));
    }

    public function testFirstReturnsEmptyStringIfNoMessagesFound()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $this->assertSame('', $container->first('foo'));
    }

    public function testFirstReturnsSingleMessageFromDotKeys()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('name.first', 'jon');
        $container->add('name.last', 'snow');
        $this->assertSame('jon', $container->first('name.*'));
    }

    public function testHasIndicatesExistence()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $this->assertTrue($container->has('foo'));
        $this->assertFalse($container->has('bar'));
    }

    public function testMissingIndicatesNonExistence()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $this->assertFalse($container->missing('foo'));
        $this->assertFalse($container->missing(['foo', 'baz']));
        $this->assertFalse($container->missing('foo', 'baz'));
        $this->assertTrue($container->missing('baz'));
        $this->assertTrue($container->missing(['baz', 'biz']));
        $this->assertTrue($container->missing('baz', 'biz'));
    }

    public function testAddIf()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->addIf(true, 'foo', 'bar');
        $this->assertTrue($container->has('foo'));

        $container->addIf(false, 'bar', 'biz');
        $this->assertFalse($container->has('bar'));
    }

    public function testForget()
    {
        $container = new MessageBag(['foo' => 'bar']);
        $container->forget('foo');
        $this->assertFalse($container->has('foo'));
    }

    public function testHasWithKeyNull()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $this->assertTrue($container->has(null));
    }

    public function testHasAnyIndicatesExistence()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $this->assertFalse($container->hasAny());
        $container->add('foo', 'bar');
        $container->add('bar', 'foo');
        $container->add('boom', 'baz');
        $this->assertTrue($container->hasAny(['foo', 'bar']));
        $this->assertTrue($container->hasAny('foo', 'bar'));
        $this->assertTrue($container->hasAny(['boom', 'baz']));
        $this->assertTrue($container->hasAny('boom', 'baz'));
        $this->assertFalse($container->hasAny(['baz']));
        $this->assertFalse($container->hasAny('baz'));
        $this->assertFalse($container->hasAny('baz', 'biz'));
    }

    public function testHasAnyWithKeyNull()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $this->assertTrue($container->hasAny(null));
    }

    public function testHasIndicatesExistenceOfAllKeys()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $container->add('bar', 'foo');
        $container->add('boom', 'baz');
        $this->assertTrue($container->has(['foo', 'bar', 'boom']));
        $this->assertFalse($container->has(['foo', 'bar', 'boom', 'baz']));
        $this->assertFalse($container->has(['foo', 'baz']));
    }

    public function testHasIndicatesNoneExistence()
    {
        $container = new MessageBag;
        $container->setFormat(':message');

        $this->assertFalse($container->has('foo'));
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
        $this->assertSame('<p>bar</p>', $container->first('foo'));
        $this->assertEquals(['<p>bar</p>'], $container->get('foo'));
        $this->assertEquals(['<p>bar</p>', '<p>baz</p>'], $container->all());
        $this->assertSame('bar', $container->first('foo', ':message'));
        $this->assertEquals(['bar'], $container->get('foo', ':message'));
        $this->assertEquals(['bar', 'baz'], $container->all(':message'));

        $container->setFormat(':key :message');
        $this->assertSame('foo bar', $container->first('foo'));
    }

    public function testUnique()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo', 'bar');
        $container->add('foo2', 'bar');
        $container->add('boom', 'baz');
        $this->assertEquals([0 => 'bar', 2 => 'baz'], $container->unique());
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

        $this->assertSame('{"foo":["bar"],"boom":["baz"]}', $container->toJson());
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

    public function testFirstFindsMessageForWildcardKey()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $container->add('foo.bar', 'baz');
        $this->assertSame('baz', $container->first('foo.*'));
    }

    public function testIsEmptyTrue()
    {
        $container = new MessageBag;
        $this->assertTrue($container->isEmpty());
    }

    public function testIsEmptyFalse()
    {
        $container = new MessageBag;
        $container->add('foo.bar', 'baz');
        $this->assertFalse($container->isEmpty());
    }

    public function testIsNotEmptyTrue()
    {
        $container = new MessageBag;
        $container->add('foo.bar', 'baz');
        $this->assertTrue($container->isNotEmpty());
    }

    public function testIsNotEmptyFalse()
    {
        $container = new MessageBag;
        $this->assertFalse($container->isNotEmpty());
    }

    public function testToString()
    {
        $container = new MessageBag;
        $container->add('foo.bar', 'baz');
        $this->assertSame('{"foo.bar":["baz"]}', (string) $container);
    }

    public function testGetFormat()
    {
        $container = new MessageBag;
        $container->setFormat(':message');
        $this->assertSame(':message', $container->getFormat());
    }

    public function testConstructorUniquenessConsistency()
    {
        $messageBag = new MessageBag(['messages' => ['first', 'second', 'third', 'third']]);
        $messages = $messageBag->getMessages();
        $this->assertEquals(['first', 'second', 'third'], $messages['messages']);

        $messageBag = new MessageBag;
        $messageBag->add('messages', 'first');
        $messageBag->add('messages', 'second');
        $messageBag->add('messages', 'third');
        $messageBag->add('messages', 'third');
        $messages = $messageBag->getMessages();
        $this->assertEquals(['first', 'second', 'third'], $messages['messages']);
    }
}
