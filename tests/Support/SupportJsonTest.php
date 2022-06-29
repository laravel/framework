<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Json;
use PHPUnit\Framework\TestCase;

class SupportJsonTest extends TestCase
{
    protected const DATA = [
        'foo' => 'bar',
        'baz' => [
            'quz' => ['qux'],
            'quuz' => [
                'quux' => 'fred',
            ],
        ],
        'corge' => 'thud',
        'null' => null,
    ];

    protected Json $json;

    protected function setUp(): void
    {
        parent::setUp();

        $this->json = new Json(static::DATA);
    }

    public function test_get()
    {
        $this->assertSame('fred', $this->json->get('baz.quuz.quux'));
        $this->assertNull($this->json->get('invalid'));
        $this->assertSame('foo', $this->json->get('invalid', 'foo'));
        $this->assertSame('foo', $this->json->get('invalid', fn() => 'foo'));
    }


    public function test_set()
    {
        $this->json->set('foo', 'quz');
        $this->assertSame('quz', $this->json->get('foo'));

        $this->json->set('baz.quuz.quux', 'corge');
        $this->assertSame('corge', $this->json->get('baz.quuz.quux'));
    }

    public function test_set_no_overwrite()
    {
        $this->json->set('foo', 'quz', false);
        $this->assertSame('bar', $this->json->get('foo'));

        $this->json->set('baz.quuz.quux', 'corge', false);
        $this->assertSame('fred', $this->json->get('baz.quuz.quux'));
    }

    public function test_has()
    {
        $this->assertTrue($this->json->has('foo'));
        $this->assertTrue($this->json->has('baz.quuz.quux'));
        $this->assertFalse($this->json->has('null'));
    }


    public function test_forget()
    {
        $this->json->forget('foo');
        $this->assertFalse($this->json->has('foo'));

        $this->json->forget('baz.quuz.quux');
        $this->assertFalse($this->json->has('baz.quuz.quux'));

        $this->json->forget('invalid');
        $this->assertArrayNotHasKey('invalid', $this->json->toArray());
    }

    public function test_forget_with_object()
    {
        $this->json->set('bar', (object) ['baz' => (object) ['quz' => 'qux']]);

        $this->json->forget('bar.baz.quz');
        $this->assertFalse($this->json->has('bar.baz.quz'));
    }

    public function test_forgets_not_applied_to_value()
    {
        $this->json->forget('foo.bar');
        $this->assertSame('bar', $this->json->get('foo'));
    }

    public function test_dynamic_access()
    {
        $this->assertSame('bar', $this->json->foo);

        $this->json->bar = 'quz';
        $this->assertSame('quz', $this->json->bar);

        $this->assertTrue(isset($this->json->bar));
        $this->assertFalse(isset($this->json->null));
        $this->assertFalse(isset($this->json->invalid));

        unset($this->json->foo);

        $this->assertFalse($this->json->has('foo'));
    }

    public function test_array_access()
    {
        $this->assertSame('bar', $this->json['foo']);

        $this->json['bar'] = 'quz';
        $this->assertSame('quz', $this->json['bar']);

        $this->assertTrue(isset($this->json['bar']));
        $this->assertFalse(isset($this->json['null']));
        $this->assertFalse(isset($this->json['invalid']));

        unset($this->json['foo']);

        $this->assertFalse($this->json->has('foo'));
    }

    public function test_to_string()
    {
        $this->assertSame(
            '{"foo":"bar","baz":{"quz":["qux"],"quuz":{"quux":"fred"}},"corge":"thud","null":null}',
            (string) $this->json
        );
    }

    public function test_json_serializable()
    {
        $this->assertSame(
            '{"foo":"bar","baz":{"quz":["qux"],"quuz":{"quux":"fred"}},"corge":"thud","null":null}',
            json_encode($this->json)
        );
    }

    public function test_to_array()
    {
        $this->assertSame(static::DATA, $this->json->toArray());
    }

    public function test_to_json()
    {
        $this->assertSame(
            '{"foo":"bar","baz":{"quz":["qux"],"quuz":{"quux":"fred"}},"corge":"thud","null":null}',
            $this->json->toJson()
        );
    }

    public function test_make()
    {
        $this->assertEmpty(Json::make()->toArray());
        $this->assertSame(['foo' => 'bar'], Json::make(['foo' => 'bar'])->toArray());
    }

    public function test_make_with_json_string()
    {
        $this->assertSame(
            static::DATA,
            Json::make(
                '{"foo":"bar","baz":{"quz":["qux"],"quuz":{"quux":"fred"}},"corge":"thud","null":null}'
            )->toArray()
        );
    }
}
