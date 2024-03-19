<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Json;
use JsonException;
use PHPUnit\Framework\TestCase;

use function json_encode;

class JsonTest extends TestCase
{
    public function testInstanceFromArrayable()
    {
        $json = new Json(new Json(['foo' => ['bar' => 'baz']]));

        $this->assertSame(['foo' => ['bar' => 'baz']], $json->items());
    }

    public function testGetsAndSetsValues()
    {
        $json = new Json(['foo' => ['bar' => 'baz']]);

        $this->assertSame('baz', $json->get('foo.bar'));

        $json->set('foo.bar', 'quz');

        $this->assertSame('quz', $json->get('foo.bar'));
    }

    public function testGetsDefaultValue()
    {
        $json = new Json(['foo' => ['bar' => 'baz']]);

        $this->assertNull($json->get('foo.invalid'));
        $this->assertSame('default', $json->get('foo.invalid', 'default'));
        $this->assertSame('default', $json->get('foo.invalid', fn () => 'default'));
    }

    public function testHasValue()
    {
        $json = new Json(['foo' => ['bar' => 'baz']]);

        $this->assertTrue($json->has('foo'));
        $this->assertFalse($json->missing('foo'));

        $this->assertTrue($json->has('foo.bar'));
        $this->assertFalse($json->missing('foo.bar'));

        $this->assertFalse($json->has('invalid'));
        $this->assertTrue($json->missing('invalid'));
    }

    public function testHasKey()
    {
        $json = new Json(['foo' => ['bar' => 'baz', 'null' => null]]);

        $this->assertTrue($json->hasKey('foo'));
        $this->assertFalse($json->missingKey('foo'));

        $this->assertTrue($json->hasKey('foo.null'));
        $this->assertFalse($json->missingKey('foo.null'));

        $this->assertFalse($json->hasKey('invalid'));
        $this->assertTrue($json->missingKey('invalid'));
    }

    public function testForgetsValue()
    {
        $json = new Json(['foo' => ['bar' => 'baz']]);

        $json->forget('foo.bar');

        $this->assertNull($json->get('foo.bar'));
    }

    public function testToJson()
    {
        $json = new Json(['foo' => ['bar' => 'baz']]);

        $this->assertSame('{"foo":{"bar":"baz"}}', $json->toJson());
        $this->assertSame(<<<'JSON'
{
    "foo": {
        "bar": "baz"
    }
}
JSON, $json->toJson(JSON_PRETTY_PRINT));
    }

    public function testToJsonOrFail()
    {
        $a = new class
        {
            public $otherObject;
        };

        $b = new class
        {
            public $otherObject;
        };

        $a->otherObject = $b;
        $b->otherObject = $a;

        $json = new Json(['😓' => $a]);

        $this->expectException(JsonException::class);

        $json->toJsonOrFail();
    }

    public function testToArray()
    {
        $this->assertSame(['foo' => ['bar' => 'baz']], (new Json(['foo' => ['bar' => 'baz']]))->toArray());

        $arrayable = new Json(['foo' => new Collection(['bar' => 'baz'])]);

        $this->assertSame(['foo' => ['bar' => 'baz']], $arrayable->toArray());
    }

    public function testJsonSerialization()
    {
        $json = new Json(['foo' => ['bar' => new Collection('baz')]]);

        $this->assertSame('{"foo":{"bar":["baz"]}}', json_encode($json));
    }

    public function testToString()
    {
        $json = new Json(['foo' => ['bar' => new Collection('baz')]]);

        $this->assertSame('{"foo":{"bar":["baz"]}}', (string) $json);
    }

    public function testObjectAccess()
    {
        $json = new Json(['foo' => ['bar' => 'baz']]);

        $this->assertSame('baz', $json->{'foo.bar'});
        $this->assertTrue(isset($json->{'foo.bar'}));

        $json->{'foo.bar'} = 'quz';

        $this->assertSame('quz', $json->{'foo.bar'});

        unset($json->{'foo.bar'});

        $this->assertNull($json->{'foo.bar'});
    }

    public function testArrayAccess()
    {
        $json = new Json(['foo' => ['bar' => 'baz']]);

        $this->assertSame('baz', $json['foo.bar']);
        $this->assertTrue(isset($json['foo.bar']));

        $json['foo.bar'] = 'quz';

        $this->assertSame('quz', $json['foo.bar']);

        unset($json['foo.bar']);

        $this->assertNull($json['foo.bar']);
    }

    public function testFromString()
    {
        $this->assertSame(['foo' => ['bar' => 'baz']], Json::fromString('{"foo":{"bar":"baz"}}')->items());
    }

    public function testMake()
    {
        $this->assertSame([], Json::make()->items());
        $this->assertSame(['foo' => ['bar' => 'baz']], Json::make(['foo' => ['bar' => 'baz']])->items());
    }
}
