<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Js;
use JsonSerializable;
use PHPUnit\Framework\TestCase;

class SupportJsTest extends TestCase
{
    public function testScalars()
    {
        $this->assertEquals('false', (string) Js::from(false));
        $this->assertEquals('true', (string) Js::from(true));
        $this->assertEquals('1', (string) Js::from(1));
        $this->assertEquals('1.1', (string) Js::from(1.1));
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

            public function jsonSerialize()
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

            public function jsonSerialize()
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
}
