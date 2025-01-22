<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Stringable;

class DatabaseCustomCastsTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_eloquent_model_with_custom_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->text('array_object');
            $table->json('array_object_json');
            $table->text('collection');
            $table->string('stringable');
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('test_eloquent_model_with_custom_casts_nullables', function (Blueprint $table) {
            $table->increments('id');
            $table->text('array_object')->nullable();
            $table->json('array_object_json')->nullable();
            $table->text('collection')->nullable();
            $table->string('stringable')->nullable();
            $table->timestamps();
        });
    }

    public function test_custom_casting()
    {
        $model = new TestEloquentModelWithCustomCasts;

        $model->array_object = ['name' => 'Taylor'];
        $model->array_object_json = ['name' => 'Taylor'];
        $model->collection = collect(['name' => 'Taylor']);
        $model->stringable = new Stringable('Taylor');
        $model->password = Hash::make('secret');

        $model->save();

        $model = $model->fresh();

        $this->assertEquals(['name' => 'Taylor'], $model->array_object->toArray());
        $this->assertEquals(['name' => 'Taylor'], $model->array_object_json->toArray());
        $this->assertEquals(['name' => 'Taylor'], $model->collection->toArray());
        $this->assertSame('Taylor', (string) $model->stringable);
        $this->assertTrue(Hash::check('secret', $model->password));

        $model->array_object['age'] = 34;
        $model->array_object['meta']['title'] = 'Developer';

        $model->array_object_json['age'] = 34;
        $model->array_object_json['meta']['title'] = 'Developer';

        $model->save();

        $model = $model->fresh();

        $this->assertEquals(
            [
                'name' => 'Taylor',
                'age' => 34,
                'meta' => ['title' => 'Developer'],
            ],
            $model->array_object->toArray()
        );

        $this->assertEquals(
            [
                'name' => 'Taylor',
                'age' => 34,
                'meta' => ['title' => 'Developer'],
            ],
            $model->array_object_json->toArray()
        );
    }

    public function test_custom_casting_using_create()
    {
        $model = TestEloquentModelWithCustomCasts::create([
            'array_object' => ['name' => 'Taylor'],
            'array_object_json' => ['name' => 'Taylor'],
            'collection' => collect(['name' => 'Taylor']),
            'stringable' => new Stringable('Taylor'),
            'password' => Hash::make('secret'),
        ]);

        $model->save();

        $model = $model->fresh();

        $this->assertEquals(['name' => 'Taylor'], $model->array_object->toArray());
        $this->assertEquals(['name' => 'Taylor'], $model->array_object_json->toArray());
        $this->assertEquals(['name' => 'Taylor'], $model->collection->toArray());
        $this->assertSame('Taylor', (string) $model->stringable);
        $this->assertTrue(Hash::check('secret', $model->password));
    }

    public function test_custom_casting_nullable_values()
    {
        $model = new TestEloquentModelWithCustomCastsNullable();

        $model->array_object = null;
        $model->array_object_json = null;
        $model->collection = collect();
        $model->stringable = null;

        $model->save();

        $model = $model->fresh();

        $this->assertEmpty($model->array_object);
        $this->assertEmpty($model->array_object_json);
        $this->assertEmpty($model->collection);
        $this->assertSame('', (string) $model->stringable);

        $model->array_object = ['name' => 'John'];
        $model->array_object['name'] = 'Taylor';
        $model->array_object['meta']['title'] = 'Developer';

        $model->array_object_json = ['name' => 'John'];
        $model->array_object_json['name'] = 'Taylor';
        $model->array_object_json['meta']['title'] = 'Developer';

        $model->save();

        $model = $model->fresh();

        $this->assertEquals(
            [
                'name' => 'Taylor',
                'meta' => ['title' => 'Developer'],
            ],
            $model->array_object->toArray()
        );

        $this->assertEquals(
            [
                'name' => 'Taylor',
                'meta' => ['title' => 'Developer'],
            ],
            $model->array_object_json->toArray()
        );
    }
}

class TestEloquentModelWithCustomCasts extends Model
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
        'array_object_json' => AsArrayObject::class,
        'collection' => AsCollection::class,
        'stringable' => AsStringable::class,
        'password' => 'hashed',
    ];
}

class TestEloquentModelWithCustomCastsNullable extends Model
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
        'array_object_json' => AsArrayObject::class,
        'collection' => AsCollection::class,
        'stringable' => AsStringable::class,
    ];
}
