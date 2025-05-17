<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsInstance;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Fluent;
use Illuminate\Support\Stringable;
use InvalidArgumentException;
use ValueError;

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

    public function test_as_instance_of_class(): void
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'instance' => AsInstance::of(InstanceAttribute::class),
        ]);

        $model->setRawAttributes([
            'instance' => json_encode(['foo' => 'bar']),
        ]);

        $this->assertInstanceOf(InstanceAttribute::class, $model->instance);
        $this->assertSame(['foo' => 'bar'], $model->instance->foo);
        $this->assertNull($model->instance->bar);

        $model->instance->bar = '2';
        $this->assertSame('2', $model->instance->bar);
    }

    public function test_as_instance_of_class_with_callable(): void
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'instance' => AsInstance::of([InstanceAttributeWithFromArray::class, 'fromArray']),
        ]);

        $model->setRawAttributes([
            'instance' => json_encode(['foo' => 1, 'bar' => 2]),
        ]);

        $this->assertInstanceOf(InstanceAttribute::class, $model->instance);
        $this->assertSame(1, $model->instance->foo);
        $this->assertSame(2, $model->instance->bar);

        $model->instance->bar = '2';
        $this->assertSame('2', $model->instance->bar);
    }

    public function test_as_instance_of_class_fails_without_argument(): void
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'instance' => AsInstance::class,
        ]);

        $model->setRawAttributes([
            'instance' => json_encode(['foo' => 1, 'bar' => 2]),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A class name must be provided to cast as an instance.');

        $model->instance;
    }

    public function test_as_instance_of_class_fails_if_not_arrayable_nor_jsonable(): void
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'instance' => AsInstance::of(InstanceAttributeWithoutContracts::class),
        ]);

        $model->setRawAttributes([
            'instance' => json_encode(['foo' => 1, 'bar' => 2]),
        ]);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage(
            'The ' . InstanceAttributeWithoutContracts::class . ' class should implement Jsonable or Arrayable contract.'
        );

        $model->instance = new InstanceAttributeWithoutContracts('foo');
    }

    public function test_as_instance_of_class_uses_jsonable_contract(): void
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'instance' => AsInstance::of(InstanceAttributeWithOnlyJsonable::class),
        ]);

        $model->setRawAttributes([
            'instance' => json_encode(['foo' => 'bar']),
        ]);

        $this->assertInstanceOf(InstanceAttributeWithOnlyJsonable::class, $model->instance);
        $this->assertSame(['foo' => 'bar'], $model->instance->foo);
        $this->assertNull($model->instance->bar);
    }

    public function test_as_instance_of_class_uses_arrayable_contract(): void
    {
        $model = new TestEloquentModelWithCustomCasts();
        $model->mergeCasts([
            'instance' => AsInstance::of(InstanceAttributeWithOnlyArrayable::class),
        ]);

        $model->setRawAttributes([
            'instance' => json_encode(['foo' => 'bar']),
        ]);

        $this->assertInstanceOf(InstanceAttributeWithOnlyArrayable::class, $model->instance);
        $this->assertSame(['foo' => 'bar'], $model->instance->foo);
        $this->assertNull($model->instance->bar);
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


class InstanceAttribute implements Jsonable, Arrayable
{
    public function __construct(public $foo, public $bar = null)
    {
        //
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        return [
            'foo' => $this->foo,
            'bar' => $this->bar,
        ];
    }
}

class InstanceAttributeWithFromArray extends InstanceAttribute
{
    public static function fromArray($array)
    {
        return new static($array['foo'], $array['bar']);
    }
}

class InstanceAttributeWithOnlyJsonable implements Jsonable
{
    public function __construct(public $foo, public $bar = null)
    {
        //
    }

    public function toJson($options = 0)
    {
        return json_encode([
            'foo' => $this->foo,
            'bar' => $this->bar,
        ]);
    }
}

class InstanceAttributeWithOnlyArrayable implements Arrayable
{
    public function __construct(public $foo, public $bar = null)
    {
        //
    }

    public function toArray()
    {
        return [
            'foo' => $this->foo,
            'bar' => $this->bar,
        ];
    }
}

class InstanceAttributeWithoutContracts
{
    public function __construct(public $foo, public $bar = null)
    {
        //
    }
}
