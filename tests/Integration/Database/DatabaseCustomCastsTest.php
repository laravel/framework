<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Fluent;
use Illuminate\Support\Stringable;
use Illuminate\Tests\App\Casts\CustomCollection;
use Illuminate\Tests\App\Casts\FluentWithCallback;
use Illuminate\Tests\App\Models\Casts\TestEloquentModelWithCustomCasts;
use Illuminate\Tests\App\Models\Casts\TestEloquentModelWithCustomCastsNullable;

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
}
