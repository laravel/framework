<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Fluent;
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

    public function test_default_casts_still_persist_null_as_json_null_literal()
    {
        $model = new TestEloquentModelWithCustomCastsNullable();
        $model->array_object_json = null;
        $model->save();

        $this->assertSame('null', $model->getRawOriginal('array_object_json'));

        $this->assertFalse(
            TestEloquentModelWithCustomCastsNullable::whereNull('array_object_json')->exists()
        );
    }

    public function test_nullable_class_casts_persist_real_null()
    {
        $model = new TestEloquentModelWithNullableCustomCasts();

        $model->array_object = null;
        $model->array_object_json = null;
        $model->collection = null;

        $model->save();

        $this->assertNull($model->getRawOriginal('array_object'));
        $this->assertNull($model->getRawOriginal('array_object_json'));
        $this->assertNull($model->getRawOriginal('collection'));

        $this->assertTrue(
            TestEloquentModelWithNullableCustomCasts::whereNull('array_object_json')->exists()
        );

        $model = $model->fresh();

        $this->assertNull($model->array_object);
        $this->assertNull($model->array_object_json);
        $this->assertNull($model->collection);

        $model->array_object_json = ['name' => 'Taylor'];
        $model->save();

        $this->assertEquals(['name' => 'Taylor'], $model->fresh()->array_object_json->toArray());
    }

    public function test_as_collection_nullable_with_custom_collection_class()
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'collection' => AsCollection::nullable(CustomCollection::class),
        ]);

        $model->collection = null;
        $this->assertNull($model->getAttributes()['collection']);

        $model->setRawAttributes(['collection' => json_encode(['foo' => 'bar'])]);

        /** @var \Illuminate\Tests\Integration\Database\CustomCollection $collection */
        $collection = $model->collection;

        $this->assertInstanceOf(CustomCollection::class, $collection);
        $this->assertSame('bar', $collection->first());
    }

    public function test_as_collection_with_map_into()
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'collection' => AsCollection::of(Fluent::class),
        ]);

        $model->setRawAttributes([
            'collection' => json_encode([['foo' => 'bar']]),
        ]);

        $this->assertInstanceOf(Fluent::class, $model->collection->first());
        $this->assertSame('bar', $model->collection->first()->foo);
    }

    public function test_as_custom_collection_with_map_into()
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'collection' => AsCollection::using(CustomCollection::class, Fluent::class),
        ]);

        $model->setRawAttributes([
            'collection' => json_encode([['foo' => 'bar']]),
        ]);

        $this->assertInstanceOf(CustomCollection::class, $model->collection);
        $this->assertInstanceOf(Fluent::class, $model->collection->first());
        $this->assertSame('bar', $model->collection->first()->foo);
    }

    public function test_as_collection_with_map_callback(): void
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'collection' => AsCollection::of([FluentWithCallback::class, 'make']),
        ]);

        $model->setRawAttributes([
            'collection' => json_encode([['foo' => 'bar']]),
        ]);

        $this->assertInstanceOf(FluentWithCallback::class, $model->collection->first());
        $this->assertSame('bar', $model->collection->first()->foo);
    }

    public function test_as_custom_collection_with_map_callback(): void
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'collection' => AsCollection::using(CustomCollection::class, [FluentWithCallback::class, 'make']),
        ]);

        $model->setRawAttributes([
            'collection' => json_encode([['foo' => 'bar']]),
        ]);

        $this->assertInstanceOf(CustomCollection::class, $model->collection);
        $this->assertInstanceOf(FluentWithCallback::class, $model->collection->first());
        $this->assertSame('bar', $model->collection->first()->foo);
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

class TestEloquentModelWithNullableCustomCasts extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'test_eloquent_model_with_custom_casts_nullables';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array
     */
    protected function casts()
    {
        return [
            'array_object' => AsArrayObject::nullable(),
            'array_object_json' => AsArrayObject::nullable(),
            'collection' => AsCollection::nullable(),
            'stringable' => AsStringable::class,
        ];
    }
}

class FluentWithCallback extends Fluent
{
    public static function make($attributes = [])
    {
        return new static($attributes);
    }
}

class CustomCollection extends Collection
{
}
