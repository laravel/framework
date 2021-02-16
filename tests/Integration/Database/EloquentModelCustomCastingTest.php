<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelDateCastingTest;

use ArrayObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @group integration
 */
class EloquentModelCustomCastingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable();
            $table->json('data')->nullable();
        });

        Model::castUsing(Uuid::class, new class() implements CastsAttributes {
            public function get($model, string $key, $value, array $attributes)
            {
                return $value === null ? null : Uuid::fromString((string) $value);
            }

            public function set($model, string $key, $value, array $attributes)
            {
                return $value === null ? null : (string) $value;
            }
        });

        Model::castUsing(ArrayObject::class, function ($arguments) {
            return AsArrayObject::castUsing($arguments);
        });
    }

    public function testCustomUuidCaster()
    {
        $uuid = Uuid::uuid4();
        $model = TestCastModel::query()->create([
            'uuid' => $uuid,
        ]);

        $this->assertInstanceOf(UuidInterface::class, $model->uuid);
        $this->assertSame((string) $uuid, (string) $model->uuid);
    }

    public function testCustomArrayObjectCaster()
    {
        $model = TestCastModel::query()->create([
            'data' => new ArrayObject(['a' => 'b']),
        ]);

        $this->assertInstanceOf(ArrayObject::class, $model->data);
        $this->assertSame(['a' => 'b'], $model->data->getArrayCopy());
    }
}

class TestCastModel extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'uuid' => Uuid::class,
        'data' => ArrayObject::class,
    ];
}
