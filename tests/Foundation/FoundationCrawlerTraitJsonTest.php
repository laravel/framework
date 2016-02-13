<?php

use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

class FoundationCrawlerTraitJsonTest extends PHPUnit_Framework_TestCase
{
    use MakesHttpRequests;

    public function testSeeJsonStructure()
    {
        $this->response = new \Illuminate\Http\Response(new JsonSerializableMixedResourcesStub);

        // At root
        $this->seeJsonStructure(['foo']);

        // Nested
        $this->seeJsonStructure(['foobar' => ['foobar_foo', 'foobar_bar']]);

        // Wildcard (repeating structure)
        $this->seeJsonStructure(['bars' => ['*' => ['bar', 'foo']]]);

        // Nested after wildcard
        $this->seeJsonStructure(['baz' => ['*' => ['foo', 'bar' => ['foo', 'bar']]]]);

        // Wildcard (repeating structure) at root
        $this->response = new \Illuminate\Http\Response(new JsonSerializableSingleResourceStub);
        $this->seeJsonStructure(['*' => ['foo', 'bar', 'foobar']]);
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
