<?php

namespace Illuminate\Tests\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class JsonResourceTest extends TestCase
{
    public function testJsonResourceNullAttributes()
    {
        $model = new class extends Model {};

        $model->setAttribute('relation_sum_column', null);
        $model->setAttribute('relation_count', null);
        $model->setAttribute('relation_exists', null);

        $resource = new JsonResource($model);

        $this->assertNotInstanceOf(MissingValue::class, $resource->whenAggregated('relation', 'column', 'sum'));
        $this->assertNotInstanceOf(MissingValue::class, $resource->whenCounted('relation'));
        $this->assertNotInstanceOf(MissingValue::class, $resource->whenExistsLoaded('relation'));

        $this->assertNull($resource->whenAggregated('relation', 'column', 'sum'));
        $this->assertNull($resource->whenCounted('relation'));
        $this->assertNull($resource->whenExistsLoaded('relation'));
    }

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
