<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseArrayObjectAndCollectionCustomCastTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_eloquent_model_with_custom_array_object_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->text('array_object');
            $table->text('collection');
            $table->timestamps();
        });
    }

    public function test_array_object_and_collection_casting()
    {
        $model = new TestEloquentModelWithCustomArrayObjectCast;

        $model->array_object = ['name' => 'Taylor'];
        $model->collection = collect(['name' => 'Taylor']);

        $model->save();

        $model = $model->fresh();

        $this->assertEquals(['name' => 'Taylor'], $model->array_object->toArray());
        $this->assertEquals(['name' => 'Taylor'], $model->collection->toArray());

        $model->array_object['age'] = 34;
        $model->array_object['meta']['title'] = 'Developer';

        $model->save();

        $model = $model->fresh();

        $this->assertEquals([
            'name' => 'Taylor',
            'age' => 34,
            'meta' => ['title' => 'Developer'],
        ], $model->array_object->toArray());
    }
}

class TestEloquentModelWithCustomArrayObjectCast extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'array_object' => AsArrayObject::class,
        'collection' => AsCollection::class,
    ];
}
