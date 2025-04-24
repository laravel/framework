<?php

namespace Illuminate\Tests\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class JsonResourceTest extends TestCase
{
    public function testJsonResourceNullAttributes()
    {
        $model = new class extends Model {
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
        $model = new class extends Model {
        };

        $resource = m::mock(JsonResource::class, ['resource' => $model])
            ->makePartial()
            ->shouldReceive('jsonSerialize')->once()->andReturn(['foo' => 'bar'])
            ->getMock();

        // Simulate a JSON error
        json_decode('{');
        $this->assertTrue(json_last_error() !== JSON_ERROR_NONE);

        $this->assertSame('{"foo":"bar"}', $resource->toJson(JSON_THROW_ON_ERROR));
    }

    public function testJsonResourceEmptyAttributesSerializeAsObjects()
    {
        // Create a request mock
        $request = new Request();
        app()->instance('request', $request);

        // Create a test model with empty array attributes
        $model = new class extends Model
        {
            protected $attributes = [
                'settings' => [],
                'options' => [],
                'meta' => [],
            ];
        };

        // Create a resource and specify which attribute should be serialized as an object
        $resource = new class($model) extends JsonResource
        {
            public function toArray($request)
            {
                return [
                    'settings' => $this->settings,
                    'options' => $this->options,
                    'meta' => $this->meta,
                ];
            }
        };

        // Configure only 'settings' to be serialized as an object
        $resource->serializeAttributesAsObjects(['settings']);

        // Convert to JSON and decode for assertions
        $json = $resource->toJson();
        $decoded = json_decode($json);

        // Assert that 'settings' is an object but other empty arrays remain as arrays
        $this->assertIsObject($decoded->settings);
        $this->assertIsArray($decoded->options);
        $this->assertIsArray($decoded->meta);
    }

    public function testJsonResourceEmptyResultSerializesAsObject()
    {
        // Create a request mock
        $request = new Request();
        app()->instance('request', $request);

        // Create an empty resource
        $resource = new class([]) extends JsonResource
        {
            public function toArray($request)
            {
                return [];
            }
        };

        // Configure the resource to serialize empty results as objects
        $resource->serializeEmptyAsObject();

        // The result should be {} instead of []
        $this->assertSame('{}', $resource->toJson());
    }
}
