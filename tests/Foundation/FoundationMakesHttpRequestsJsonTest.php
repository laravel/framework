<?php

use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

class FoundationMakesHttpRequestsJsonTest extends PHPUnit_Framework_TestCase
{
    use MakesHttpRequests;

    public function testSeeJsonWithArray()
    {
        $this->response = new Illuminate\Http\Response(new JsonSerializableSingleResourceStub);

        $resource = new JsonSerializableSingleResourceStub;

        $this->seeJson($resource->jsonSerialize());
    }

    public function testSeeJsonWithMixed()
    {
        $this->response = new Illuminate\Http\Response(new JsonSerializableMixedResourcesStub);

        $resource = new JsonSerializableMixedResourcesStub;

        $this->seeJson($resource->jsonSerialize());
    }

    public function testSeeJsonDeeplyNestedPart()
    {
        $this->response = new Illuminate\Http\Response(new JsonSerializableMixedResourcesStub);

        $this->seeJson(['bar' => ['foo' => 'bar 0', 'bar' => 'foo 0']]);
    }

    public function testSeeJsonStructure()
    {
        $this->response = new Illuminate\Http\Response(new JsonSerializableMixedResourcesStub);

        // At root
        $this->seeJsonStructure(['foo']);

        // Nested
        $this->seeJsonStructure(['foobar' => ['foobar_foo', 'foobar_bar']]);

        // Wildcard (repeating structure)
        $this->seeJsonStructure(['bars' => ['*' => ['bar', 'foo']]]);

        // Nested after wildcard
        $this->seeJsonStructure(['baz' => ['*' => ['foo', 'bar' => ['foo', 'bar']]]]);

        // Wildcard (repeating structure) at root
        $this->response = new Illuminate\Http\Response(new JsonSerializableSingleResourceStub);
        $this->seeJsonStructure(['*' => ['foo', 'bar', 'foobar']]);
    }

    public function testSeeJsonTypedStructure()
    {
        $this->response = new Illuminate\Http\Response(new JsonSerializableTypedResourceStub);

        $this->seeJsonTypedStructure([
            'foo' => 'string',
            'bar' => 'integer',
            'foobar' => 'array',
            'baz' => 'boolean',
            'nested_foo' => [
                'foo' => 'string',
                'bar' => 'integer',
                'foobar' => 'array',
                'baz' => 'boolean',
                'double_nested_foo' => [
                    'foo' => 'string',
                    'bar' => 'integer',
                    'foobar' => 'array',
                    'baz' => 'boolean',
                ],
            ],
        ]);
    }
}

class JsonSerializableMixedResourcesStub implements JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            'foo' => 'bar',
            'foobar' => [
                'foobar_foo' => 'foo',
                'foobar_bar' => 'bar',
            ],
            'bars' => [
                ['bar' => 'foo 0', 'foo' => 'bar 0'],
                ['bar' => 'foo 1', 'foo' => 'bar 1'],
                ['bar' => 'foo 2', 'foo' => 'bar 2'],
            ],
            'baz' => [
                ['foo' => 'bar 0', 'bar' => ['foo' => 'bar 0', 'bar' => 'foo 0']],
                ['foo' => 'bar 1', 'bar' => ['foo' => 'bar 1', 'bar' => 'foo 1']],
            ],
        ];
    }
}

class JsonSerializableSingleResourceStub implements JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            ['foo' => 'foo 0', 'bar' => 'bar 0', 'foobar' => 'foobar 0'],
            ['foo' => 'foo 1', 'bar' => 'bar 1', 'foobar' => 'foobar 1'],
            ['foo' => 'foo 2', 'bar' => 'bar 2', 'foobar' => 'foobar 2'],
            ['foo' => 'foo 3', 'bar' => 'bar 3', 'foobar' => 'foobar 3'],
        ];
    }
}

class JsonSerializableTypedResourceStub implements JsonSerializable
{
    public function jsonSerialize()
    {
        return json_decode('{
            "foo": "bar",
            "bar": 42,
            "foobar": [
                "foo",
                "bar"
            ],
            "baz": true,
            "nested_foo": {
                "foo": "bar",
                "bar": 42,
                "foobar": [
                    "foo",
                    "bar"
                ],
                "baz": false,
                "double_nested_foo": {
                    "foo": "bar",
                    "bar": 42,
                    "foobar": [
                        "foo",
                        "bar"
                    ],
                    "baz": false
                }
            }
        }');
    }
}
