<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsCollectionMap;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Fluent;
use Illuminate\Support\Stringable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

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

    public function test_custom_casting_map_into_collection(): void
    {
        $model = new TestEloquentModelWithCustomCastsNullable();
        $model->mergeCasts(['collection' => AsCollectionMap::into(Fluent::class)]);
        $model->fill([
            'collection' => [
                ['name' => 'Taylor'],
            ],
        ]);

        $fluent = $model->collection->first();

        $this->assertInstanceOf(Fluent::class, $fluent);
        $this->assertSame('Taylor', $fluent->name);
    }

    public static function provideMapArguments()
    {
        return [
            [TestCollectionMapCallable::class],
            [TestCollectionMapCallable::class, 'make'],
            [TestCollectionMapCallable::class.'@'.'make'],
        ];
    }

    #[DataProvider('provideMapArguments')]
    public function test_custom_casting_map_collection($class, $method = null): void
    {
        $model = new TestEloquentModelWithCustomCastsNullable();
        $model->mergeCasts(['collection' => AsCollectionMap::using($class, $method)]);
        $model->fill([
            'collection' => [
                ['name' => 'Taylor'],
            ],
        ]);

        $result = $model->collection->first();

        $this->assertInstanceOf(TestCollectionMapCallable::class, $result);
        $this->assertSame('Taylor', $result->name);
    }

    public function test_custom_casting_map_collection_throw_when_no_arguments(): void
    {
        $model = new TestEloquentModelWithCustomCastsNullable();
        $model->mergeCasts(['collection' => AsCollectionMap::class]);
        $model->fill([
            'collection' => [
                ['name' => 'Taylor'],
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No class or callable has been set to map the Collection.');

        $model->collection->first();
    }

    public function test_custom_casting_map_collection_throw_when_using_closure(): void
    {
        $model = new TestEloquentModelWithCustomCastsNullable();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided callback should be a callable array or string.');

        $model->mergeCasts(['collection' => AsCollectionMap::using(TestCollectionMapCallable::make(...))]);
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

class TestCollectionMapCallable
{
    public $name;

    public function __construct($data)
    {
        $this->name = $data['name'];
    }

    public static function make($data)
    {
        return new static($data);
    }
}
