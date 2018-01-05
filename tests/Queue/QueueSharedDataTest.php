<?php

namespace Illuminate\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Illuminate\Queue\SharedData;

class QueueSharedDataTest extends TestCase
{
    public function testEmptyCollectionIsEmpty()
    {
        $c = new SharedData();

        $this->assertTrue($c->isEmpty());
    }

    public function testEmptyCollectionIsNotEmpty()
    {
        $c = new SharedData(['foo', 'bar']);

        $this->assertFalse($c->isEmpty());
        $this->assertTrue($c->isNotEmpty());
    }

    public function testGet()
    {
        $c = new SharedData(['foo' => 'bar']);
        $this->assertSame('bar', $c->get('foo'));
        $this->assertSame('foo', $c->get('foobar', 'foo'));
    }

    public function testPut()
    {
        $c = new SharedData(['foo', 'bar']);

        $c->put('one', 'two');
        $this->assertSame(['foo', 'bar', 'one' => 'two'], $c->toArray());
    }

    public function testSerializingAndUnserializing()
    {
        $array = ['foo' => 'bar', 'bar' => ['salt' => 'pepper']];

        $c = new SharedData($array);
        $serialized = serialize($c);
        $this->assertStringStartsWith('C:27:"Illuminate\Queue\SharedData"', $serialized);

        $d = unserialize($serialized);
        $this->assertInstanceOf(\Illuminate\Queue\SharedData::class, $d);
        $this->assertSame($array, $d->toArray());
    }
}