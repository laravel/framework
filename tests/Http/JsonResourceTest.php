<?php

namespace Illuminate\Tests\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class JsonResourceTest extends TestCase
{
    public function testJsonResourceToJsonSucceedsWithPriorErrors(): void
    {
        $model = new class extends Model {};

        $resource = m::mock(JsonResource::class, ['resource' => $model])
            ->makePartial()
            ->shouldReceive('jsonSerialize')->once()->andReturn(['foo' => 'bar'])
            ->getMock();

        // Simulate a JSON error
        json_decode('{');
        $this->assertTrue(json_last_error() !== JSON_ERROR_NONE);

        $this->assertSame('{"foo":"bar"}', $resource->toJson(JSON_THROW_ON_ERROR));
    }
}
