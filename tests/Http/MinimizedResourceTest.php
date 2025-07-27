<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\MinimizedResource;
use Illuminate\Http\Resources\MissingValue;
use PHPUnit\Framework\TestCase;

class DummyResource extends MinimizedResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->when($this->show('id'), $this->id),
            'name' => $this->when($this->show('name'), $this->name),
            'email' => $this->when($this->show('email'), $this->email),
        ];
    }
}

class MinimizedResourceTest extends TestCase
{
    public function testShowAllFieldsWhenOnlyIsEmpty()
    {
        $resource = new DummyResource(['id' => 1, 'name' => 'Mahmoued', 'email' => 'mahmoued@example.com']);

        $this->assertTrue($this->callProtected($resource, 'show', ['id']));
        $this->assertTrue($this->callProtected($resource, 'show', ['name']));
        $this->assertTrue($this->callProtected($resource, 'show', ['email']));
    }

    public function testShowLimitedFieldsWhenOnlyIsSpecified()
    {
        $resource = new DummyResource(['id' => 1, 'name' => 'Mahmoued', 'email' => 'mahmoued@example.com'], ['id', 'email']);

        $this->assertTrue($this->callProtected($resource, 'show', ['id']));
        $this->assertTrue($this->callProtected($resource, 'show', ['email']));
        $this->assertFalse($this->callProtected($resource, 'show', ['name']));
    }

    /**
     * Helper to call protected method
     */
    private function callProtected($object, $method, array $args = [])
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($object, $args);
    }
}
