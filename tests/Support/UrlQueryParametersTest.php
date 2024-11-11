<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\UrlQueryParameters;
use PHPUnit\Framework\TestCase;

class UrlQueryParametersTest extends TestCase
{
    public function testParse()
    {
        $params = UrlQueryParameters::parse('foo=bar&baz=qux');

        $this->assertInstanceOf(UrlQueryParameters::class, $params);
        $this->assertSame('bar', $params->get('foo'));
        $this->assertSame('qux', $params->get('baz'));
    }

    public function testParseWithEmptyString()
    {
        $params = UrlQueryParameters::parse('');

        $this->assertInstanceOf(UrlQueryParameters::class, $params);
        $this->assertEmpty($params->toArray());
    }

    public function testParseWithNull()
    {
        $params = UrlQueryParameters::parse(null);

        $this->assertInstanceOf(UrlQueryParameters::class, $params);
        $this->assertEmpty($params->toArray());
    }

    public function testParseWithEncodedCharacters()
    {
        $params = UrlQueryParameters::parse('test=1%2B2');

        $this->assertInstanceOf(UrlQueryParameters::class, $params);
        $this->assertSame('1+2', $params->get('test'));
    }

    public function testGet()
    {
        $params = new UrlQueryParameters(['foo' => 'bar']);

        $this->assertSame('bar', $params->get('foo'));
        $this->assertNull($params->get('baz'));
        $this->assertSame('default', $params->get('baz', 'default'));
    }

    public function testSet()
    {
        $params = new UrlQueryParameters(['foo' => 'bar']);

        $params->set('baz', 'qux');

        $this->assertSame('qux', $params->get('baz'));
    }

    public function testForget()
    {
        $params = new UrlQueryParameters(['foo' => 'bar']);

        $params->forget('foo');

        $this->assertNull($params->get('foo'));
    }

    public function testClear()
    {
        $params = new UrlQueryParameters(['foo' => 'bar']);

        $params->clear();

        $this->assertEmpty($params->toArray());
    }

    public function testHas()
    {
        $params = new UrlQueryParameters(['foo' => 'bar']);

        $this->assertTrue($params->has('foo'));
        $this->assertFalse($params->has('baz'));
    }

    public function testAll()
    {
        $params = new UrlQueryParameters([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'qux',
        ], $params->all());
    }

    public function testIsEmpty()
    {
        $params = new UrlQueryParameters;

        $this->assertTrue($params->isEmpty());

        $params = new UrlQueryParameters(['foo' => 'bar']);

        $this->assertFalse($params->isEmpty());
    }

    public function testArrayAccess()
    {
        $params = new UrlQueryParameters([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertSame('bar', $params['foo']);
        $this->assertSame('qux', $params['baz']);

        $this->assertFalse(isset($params['zax']));

        $params['zax'] = 'baz';

        $this->assertSame('baz', $params['zax']);
    }

    public function testToArray()
    {
        $params = new UrlQueryParameters([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'qux',
        ], $params->toArray());
    }

    public function testToString()
    {
        $params = new UrlQueryParameters([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertSame('foo=bar&baz=qux', (string) $params);
    }
}
