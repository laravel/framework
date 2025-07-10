<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Js;
use Illuminate\Tests\Support\Fixtures\IntBackedEnum;
use Illuminate\Tests\Support\Fixtures\StringBackedEnum;
use JsonSerializable;
use PHPUnit\Framework\TestCase;

class SupportJsTest extends TestCase
{
    public function testScalars()
    {
        $this->assertSame('false', (string) Js::from(false));
        $this->assertSame('true', (string) Js::from(true));
        $this->assertSame('1', (string) Js::from(1));
        $this->assertSame('1.1', (string) Js::from(1.1));
        $this->assertSame('[]', (string) Js::from([]));
        $this->assertSame('[]', (string) Js::from(collect()));
        $this->assertSame('null', (string) Js::from(null));
        $this->assertSame("'Hello world'", (string) Js::from('Hello world'));
        $this->assertEquals(
            "'\\u003Cdiv class=\\u0022foo\\u0022\\u003E\\u0027quoted html\\u0027\\u003C\\/div\\u003E'",
            (string) Js::from('<div class="foo">\'quoted html\'</div>')
        );
    }

    public function testArrays()
    {
        $this->assertEquals(
            "JSON.parse('[\\u0022hello\\u0022,\\u0022world\\u0022]')",
            (string) Js::from(['hello', 'world'])
        );

        $this->assertEquals(
            "JSON.parse('{\\u0022foo\\u0022:\\u0022hello\\u0022,\\u0022bar\\u0022:\\u0022world\\u0022}')",
            (string) Js::from(['foo' => 'hello', 'bar' => 'world'])
        );
    }

    public function testObjects()
    {
        $this->assertEquals(
            "JSON.parse('{\\u0022foo\\u0022:\\u0022hello\\u0022,\\u0022bar\\u0022:\\u0022world\\u0022}')",
            (string) Js::from((object) ['foo' => 'hello', 'bar' => 'world'])
        );
    }

    public function testJsonSerializable()
    {
        // JsonSerializable should take precedence over Arrayable, so we'll
        // implement both and make sure the correct data is used.
        $data = new class() implements JsonSerializable, Arrayable
        {
            public $foo = 'not hello';

            public $bar = 'not world';

            public function jsonSerialize(): mixed
            {
                return ['foo' => 'hello', 'bar' => 'world'];
            }

            public function toArray()
            {
                return ['foo' => 'not hello', 'bar' => 'not world'];
            }
        };

        $this->assertEquals(
            "JSON.parse('{\\u0022foo\\u0022:\\u0022hello\\u0022,\\u0022bar\\u0022:\\u0022world\\u0022}')",
            (string) Js::from($data)
        );
    }

    public function testJsonable()
    {
        // Jsonable should take precedence over JsonSerializable and Arrayable, so we'll
        // implement all three and make sure the correct data is used.
        $data = new class() implements Jsonable, JsonSerializable, Arrayable
        {
            public $foo = 'not hello';

            public $bar = 'not world';

            public function toJson($options = 0)
            {
                return json_encode(['foo' => 'hello', 'bar' => 'world'], $options);
            }

            public function jsonSerialize(): mixed
            {
                return ['foo' => 'not hello', 'bar' => 'not world'];
            }

            public function toArray()
            {
                return ['foo' => 'not hello', 'bar' => 'not world'];
            }
        };

        $this->assertEquals(
            "JSON.parse('{\\u0022foo\\u0022:\\u0022hello\\u0022,\\u0022bar\\u0022:\\u0022world\\u0022}')",
            (string) Js::from($data)
        );
    }

    public function testArrayable()
    {
        $data = new class() implements Arrayable
        {
            public $foo = 'not hello';

            public $bar = 'not world';

            public function toArray()
            {
                return ['foo' => 'hello', 'bar' => 'world'];
            }
        };

        $this->assertEquals(
            "JSON.parse('{\\u0022foo\\u0022:\\u0022hello\\u0022,\\u0022bar\\u0022:\\u0022world\\u0022}')",
            (string) Js::from($data)
        );
    }

    public function testHtmlable()
    {
        $data = new class implements Htmlable
        {
            public function toHtml()
            {
                return '<p>Hello, World!</p>';
            }
        };

        $this->assertEquals("'\u003Cp\u003EHello, World!\u003C\/p\u003E'", (string) Js::from($data));

        $data = new class implements Htmlable, Arrayable
        {
            public function toHtml()
            {
                return '<p>Hello, World!</p>';
            }

            public function toArray()
            {
                return ['foo' => 'hello', 'bar' => 'world'];
            }
        };

        $this->assertEquals(
            "JSON.parse('{\\u0022foo\\u0022:\\u0022hello\\u0022,\\u0022bar\\u0022:\\u0022world\\u0022}')",
            (string) Js::from($data)
        );

        $data = new class implements Htmlable, Jsonable
        {
            public function toHtml()
            {
                return '<p>Hello, World!</p>';
            }

            public function toJson($options = 0)
            {
                return json_encode(['foo' => 'hello', 'bar' => 'world'], $options);
            }
        };

        $this->assertEquals(
            "JSON.parse('{\\u0022foo\\u0022:\\u0022hello\\u0022,\\u0022bar\\u0022:\\u0022world\\u0022}')",
            (string) Js::from($data)
        );

        $data = new class implements Htmlable, JsonSerializable
        {
            public function toHtml()
            {
                return '<p>Hello, World!</p>';
            }

            public function jsonSerialize(): mixed
            {
                return ['foo' => 'hello', 'bar' => 'world'];
            }
        };

        $this->assertEquals(
            "JSON.parse('{\\u0022foo\\u0022:\\u0022hello\\u0022,\\u0022bar\\u0022:\\u0022world\\u0022}')",
            (string) Js::from($data)
        );
    }

    public function testBackedEnums()
    {
        $this->assertSame('2', (string) Js::from(IntBackedEnum::TWO));
        $this->assertSame("'Hello world'", (string) Js::from(StringBackedEnum::HELLO_WORLD));
    }
}
