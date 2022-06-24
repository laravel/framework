<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Json;

class SupportJsonTest extends TestCase
{
    /**
     * @param  array  $array
     * @return \Illuminate\Support\Json
     */
    protected function json($array = [])
    {
        return new Json($array);
    }

    public function testAll()
    {
        $this->assertSame(['foo' => 'bar'], $this->json(['foo' => 'bar'])->all());
    }

    public function testGet()
    {
        $json = $this->json(['foo' => ['bar' => 'baz']]);

        $this->assertSame('baz', $json->get('foo.bar'));

        $this->assertNull($json->get('foo.baz'));

        $this->assertSame('qux', $json->get('foo.baz', 'qux'));
    }

    public function testHas()
    {
        $json = $this->json(['foo' => ['bar' => 'baz']]);

        $this->assertTrue($json->has('foo.bar'));
        $this->assertFalse($json->has('foo.baz'));

        $this->assertTrue($json->has('foo', 'foo.bar'));
        $this->assertFalse($json->has('foo', 'foo.baz'));
    }

    public function testHasAny()
    {
        $json = $this->json(['foo' => ['bar' => 'baz']]);

        $this->assertTrue($json->hasAny('foo.bar'));
        $this->assertFalse($json->hasAny('foo.baz'));

        $this->assertTrue($json->hasAny('foo', 'foo.bar'));
        $this->assertTrue($json->hasAny('foo', 'foo.baz'));
    }

    public function testMissing()
    {
        $json = $this->json(['foo' => ['bar' => 'baz']]);

        $this->assertFalse($json->missing('foo.bar'));
        $this->assertTrue($json->missing('foo.baz'));

        $this->assertFalse($json->missing('foo.bar'));
        $this->assertTrue($json->missing('foo.baz'));
    }

    public function testSet()
    {
        $json = $this->json(['foo' => ['bar' => 'baz']]);

        $json->set('foo.quz', 'qux');

        $this->assertSame('qux', $json->get('foo.quz'));

        $json->set(null, []);

        $this->assertSame([], $json->all());
    }

    public function testForget()
    {
        $json = $this->json(['foo' => ['bar' => 'baz', 'quz' => 'qux']]);

        $json->forget('foo.quz');

        $this->assertSame(['foo' => ['bar' => 'baz']], $json->all());
    }

    public function testCollection()
    {
        $json = $this->json(['foo' => ['bar' => 'baz', 'quz' => 'qux']]);

        $this->assertSame(['foo' => ['bar' => 'baz', 'quz' => 'qux']], $json->collect()->all());

        $this->assertSame(['bar' => 'baz', 'quz' => 'qux'], $json->collect('foo')->all());
    }

    public function testDynamicAccess()
    {
        $json = $this->json(['foo' => ['bar' => 'baz']]);

        $this->assertSame(['bar' => 'baz'], $json->foo);

        $json->foo = ['quz'];

        $this->assertSame(['quz'], $json->foo);

        $this->assertTrue(isset($json->foo));

        unset($json->foo);

        $this->assertNull($json->foo);
    }

    public function testArrayAccess()
    {
        $json = $this->json(['foo' => ['bar' => 'baz']]);

        $this->assertSame(['bar' => 'baz'], $json['foo']);

        $json['foo'] = ['quz'];

        $this->assertSame(['quz'], $json['foo']);

        $this->assertTrue(isset($json['foo']));

        unset($json['foo']);

        $this->assertNull($json['foo']);
    }

    public function testToString()
    {
        $json = $this->json(['foo' => ['bar' => 'baz']]);

        $this->assertSame('{"foo":{"bar":"baz"}}', $json->__toString());
        $this->assertSame('{"foo":{"bar":"baz"}}', (string) $json);
    }

    public function testIterator()
    {
        $json = $this->json($array = ['foo', 'bar', 'baz', 'quz']);

        foreach ($json as $key => $value) {
            $this->assertSame($array[$key], $value);
        }
    }

    public function testToArray()
    {
        $json = $this->json(['foo', 'bar', 'baz', new Collection(['quz', 'qux'])]);

        $this->assertSame(['foo', 'bar', 'baz', ['quz', 'qux']], $json->toArray());
    }

    public function testToJson()
    {
        $json = $this->json(['foo' => ['bar' => 'baz']]);

        $this->assertSame('{"foo":{"bar":"baz"}}', $json->toJson());
    }

    public function testMake()
    {
        $array = ['foo' => ['bar' => 'baz']];

        $this->assertSame($array, Json::make($array)->all());
    }

    public function testFromJson()
    {
        $this->assertSame(['foo' => ['bar' => 'baz']], Json::fromJson('{"foo":{"bar":"baz"}}')->all());
    }

    public function testWrap()
    {
        $json = $this->json(['foo' => ['bar' => 'baz']]);

        $this->assertSame($json, Json::wrap($json));

        $this->assertSame(['foo' => ['bar' => 'baz']], Json::wrap(['foo' => ['bar' => 'baz']])->all());
    }
}
