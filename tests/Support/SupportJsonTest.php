<?php

namespace Illuminate\Tests\Support;

use ArrayIterator;
use Illuminate\Support\Collection;
use Illuminate\Support\Json;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;
use RecursiveArrayIterator;
use Traversable;

class SupportJsonTest extends TestCase
{
    protected $json;

    protected function setUp(): void
    {
        parent::setUp();

        $this->json = new Json(new class
        {
            public function __construct($foo = ['foo' => 'bar'])
            {
                $this->foo = $foo;
                $this->bar = (object) ['baz' => (object) ['quz' => 'qux']];
            }
        });
    }

    public function testGet(): void
    {
        static::assertSame(['foo' => 'bar'], $this->json->get('foo'));
        static::assertSame('qux', $this->json->get('bar.baz.quz'));
        static::assertSame('cougar', $this->json->get('baz', 'cougar'));
        static::assertSame('cougar', $this->json->get('baz', fn () => 'cougar'));
        static::assertNull($this->json->get('baz'));
    }

    public function testSet(): void
    {
        $this->json->set('quz', 'qux');

        static::assertSame('qux', $this->json->get('quz'));

        $this->json->set('foo', 'bar');

        static::assertSame('bar', $this->json->get('foo'));
    }

    public function testFill(): void
    {
        $this->json->fill('quz', 'qux');

        static::assertSame('qux', $this->json->get('quz'));

        $this->json->fill('foo', 'bar');

        static::assertSame(['foo' => 'bar'], $this->json->get('foo'));
    }

    public function testHas(): void
    {
        $this->json->set('quz', ['bar' => null]);

        static::assertTrue($this->json->has('foo'));
        static::assertTrue($this->json->has('bar.baz.quz'));
        static::assertFalse($this->json->has('quz.bar'));
        static::assertFalse($this->json->has('quz.baz'));
    }

    public function testMissing(): void
    {
        $this->json->set('quz', ['bar' => null]);

        static::assertFalse($this->json->missing('foo'));
        static::assertFalse($this->json->missing('bar.baz.quz'));
        static::assertTrue($this->json->missing('quz.bar'));
        static::assertTrue($this->json->missing('quz.baz'));
    }

    public function testForget(): void
    {
        $this->json->forget('foo');
        static::assertTrue($this->json->missing('foo'));

        $this->json->forget('bar.baz.quz');
        static::assertFalse($this->json->missing('bar'));
        static::assertFalse($this->json->missing('bar.baz'));
        static::asserttrue($this->json->missing('bar.baz.quz'));

        $this->json->forget('bar.baz');
        static::assertFalse($this->json->missing('bar'));
        static::assertTrue($this->json->missing('bar.baz'));

        $this->json->forget('bar.baz');
        static::assertFalse($this->json->missing('bar'));
        static::assertTrue($this->json->missing('bar.baz'));
    }

    public function testCollect(): void
    {
        $collection = $this->json->collect();

        static::assertTrue($collection->has('foo'));
        static::assertTrue($collection->has('bar'));

        $collection = $this->json->collect('bar');

        static::assertTrue($collection->has('baz'));
    }

    public function testDynamicAccess(): void
    {
        static::assertSame(['foo' => 'bar'], $this->json->foo);

        $this->json->foo = 'bar';
        static::assertSame('bar', $this->json->foo);

        static::assertTrue(isset($this->json->bar));
        static::assertFalse(isset($this->json->baz));

        unset($this->json->foo);

        static::assertTrue($this->json->missing('foo'));
    }

    public function testArrayAccess(): void
    {
        static::assertSame(['foo' => 'bar'], $this->json['foo']);

        $this->json['foo'] = 'bar';
        static::assertSame('bar', $this->json['foo']);

        static::assertTrue(isset($this->json['bar']));
        static::assertFalse(isset($this->json['baz']));

        unset($this->json['foo']);

        static::assertTrue($this->json->missing('foo'));
    }

    public function testToStringAsJson(): void
    {
        static::assertSame('{"foo":{"foo":"bar"},"bar":{"baz":{"quz":"qux"}}}', (string) $this->json);
    }

    public function testToJson(): void
    {
        static::assertSame('{"foo":{"foo":"bar"},"bar":{"baz":{"quz":"qux"}}}', $this->json->toJson());
    }

    public function testToArray(): void
    {
        $this->json->set('baz', new Collection(['foo', 'bar', 'baz']));

        static::assertEquals([
            'foo' => ['foo' => 'bar'],
            'bar' => (object) ['baz' => (object) ['quz' => 'qux']],
            'baz' => ['foo', 'bar', 'baz'],
        ],
            $this->json->toArray()
        );
    }

    public function testIterator(): void
    {
        static::assertEquals(
            ['foo' => ['foo' => 'bar'], 'bar' => (object) ['baz' => (object) ['quz' => 'qux']]],
            iterator_to_array($this->json)
        );

        $json = new Json(['foo', 'bar', 'baz']);

        static::assertInstanceOf(ArrayIterator::class, $json->getIterator());
        static::assertSame(['foo', 'bar', 'baz'], iterator_to_array($json));

        $json = new Json(new class
        {
            public function __construct(public $foo = 'bar', public $baz = 'quz', public $qux = 'cougar')
            {
            }
        });

        static::assertInstanceOf(ArrayIterator::class, $json->getIterator());
        static::assertSame(['foo' => 'bar', 'baz' => 'quz', 'qux' => 'cougar'], iterator_to_array($json));

        $json = new Json(new class implements IteratorAggregate
        {
            public function getIterator(): Traversable
            {
                return new RecursiveArrayIterator(['foo' => ['bar', 'quz']]);
            }
        });

        static::assertInstanceOf(RecursiveArrayIterator::class, $json->getIterator());
        static::assertSame(['foo' => ['bar', 'quz']], iterator_to_array($json));
    }

    public function testMake(): void
    {
        $json = Json::make(['foo' => 'bar']);
        static::assertSame(['foo' => 'bar'], $json->data());

        $json = Json::make($object = (object) ['foo' => 'bar']);
        static::assertSame($object, $json->data());
    }

    public function testFromString(): void
    {
        $json = Json::fromString('{"foo":{"foo":"bar"}}');
        static::assertEquals((object) ['foo' => (object) ['foo' => 'bar']], $json->data());
    }

    public function testWrap(): void
    {
        $json = Json::fromString('{"foo":{"foo":"bar"}}');

        static::assertSame($json, Json::wrap($json));

        $object = (object) ['foo' => (object) ['foo' => 'bar']];

        static::assertSame($object, Json::wrap($object)->data());

        static::assertEmpty(Json::wrap(null)->data());
    }
}
