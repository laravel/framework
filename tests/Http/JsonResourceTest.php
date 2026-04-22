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
        $model = new class extends Model
        {
        };

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
        $model = new class extends Model
        {
        };

        $resource = m::mock(JsonResource::class, ['resource' => $model])
            ->makePartial()
            ->shouldReceive('jsonSerialize')->andReturn(['foo' => 'bar'])
            ->getMock();

        // Simulate a JSON error
        json_decode('{');
        $this->assertNotSame(JSON_ERROR_NONE, json_last_error());

        $this->assertSame('{"foo":"bar"}', $resource->toJson(JSON_THROW_ON_ERROR));
    }

    public function testJsonResourceToPrettyPrint(): void
    {
        $model = new class extends Model
        {
        };

        $resource = m::mock(JsonResource::class, ['resource' => $model])
            ->makePartial()
            ->shouldReceive('jsonSerialize')->andReturn(['foo' => 'bar', 'bar' => 'foo', 'number' => 123])
            ->getMock();

        $results = $resource->toPrettyJson();
        $expected = $resource->toJson(JSON_PRETTY_PRINT);

        $this->assertJsonStringEqualsJsonString($expected, $results);
        $this->assertSame($expected, $results);
        $this->assertStringContainsString("\n", (string) $results);
        $this->assertStringContainsString('    ', (string) $results);

        $results = $resource->toPrettyJson(JSON_NUMERIC_CHECK);
        $this->assertStringContainsString("\n", (string) $results);
        $this->assertStringContainsString('    ', (string) $results);
        $this->assertStringContainsString('"number": 123', (string) $results);
    }
}
