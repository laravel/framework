<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Concerns\HasGeneratedColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentModelTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('nullable_date')->nullable();
        });

        Schema::create('test_model2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
        });
    }

    public function testUserCanUpdateNullableDate()
    {
        $user = TestModel1::create([
            'nullable_date' => null,
        ]);

        $user->fill([
            'nullable_date' => $now = Carbon::now(),
        ]);
        $this->assertTrue($user->isDirty('nullable_date'));

        $user->save();
        $this->assertEquals($now->toDateString(), $user->nullable_date->toDateString());
    }

    public function testAttributeChanges()
    {
        $user = TestModel2::create([
            'name' => Str::random(), 'title' => Str::random(),
        ]);

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->name = $name = Str::random();

        $this->assertEquals(['name' => $name], $user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertTrue($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->save();

        $this->assertEmpty($user->getDirty());
        $this->assertEquals(['name' => $name], $user->getChanges());
        $this->assertTrue($user->wasChanged());
        $this->assertTrue($user->wasChanged('name'));
    }

    public function testDiscardChanges()
    {
        $user = TestModel2::create([
            'name' => $originalName = Str::random(), 'title' => Str::random(),
        ]);

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->name = $overrideName = Str::random();

        $this->assertEquals(['name' => $overrideName], $user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertTrue($user->isDirty());
        $this->assertFalse($user->wasChanged());
        $this->assertSame($originalName, $user->getOriginal('name'));
        $this->assertSame($overrideName, $user->getAttribute('name'));

        $user->discardChanges();

        $this->assertEmpty($user->getDirty());
        $this->assertSame($originalName, $user->getOriginal('name'));
        $this->assertSame($originalName, $user->getAttribute('name'));

        $user->save();
        $this->assertFalse($user->wasChanged());
    }

    public function testInsertRecordWithReservedWordFieldName()
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->timestamp('start');
            $table->timestamp('end')->nullable();
            $table->boolean('analyze');
        });

        $model = new class extends Model
        {
            protected $table = 'actions';
            protected $guarded = ['id'];
            public $timestamps = false;
        };

        $model->newInstance()->create([
            'label' => 'test',
            'start' => '2023-01-01 00:00:00',
            'end' => '2024-01-01 00:00:00',
            'analyze' => true,
        ]);

        $this->assertDatabaseHas('actions', [
            'label' => 'test',
            'start' => '2023-01-01 00:00:00',
            'end' => '2024-01-01 00:00:00',
            'analyze' => true,
        ]);
    }

    public function testHasGeneratedColumns()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('price');

            if ($this->driver === 'sqlsrv') {
                $table->computed('tax', 'price * 0.6');
                $table->computed('total', 'price * 1.6')->persisted();
            } else {
                if ($this->driver === 'pgsql') {
                    $table->integer('tax')->storedAs('price * 0.6');
                } else {
                    $table->integer('tax')->virtualAs('price * 0.6');
                }
                $table->integer('total')->storedAs('price * 1.6');
            }
        });

        Product::saved(function ($product) {
            $this->assertEquals(20, $product->price);
            $this->assertEquals(12, $product->tax);
            $this->assertEquals(32, $product->total);
        });

        $instance = Product::create(['price' => 20]);

        $this->assertEquals(20, $instance->price);
        $this->assertEquals(12, $instance->tax);
        $this->assertEquals(32, $instance->total);

        Product::flushEventListeners();
        Product::saved(function ($product) {
            $this->assertTrue($product->isDirty('price'));
            $this->assertTrue($product->isDirty('tax'));
            $this->assertTrue($product->isDirty('total'));
            $this->assertEquals(10, $product->price);
            $this->assertEquals(6, $product->tax);
            $this->assertEquals(16, $product->total);
        });

        $instance->update(['price' => 10]);

        $this->assertEquals(10, $instance->price);
        $this->assertEquals(6, $instance->tax);
        $this->assertEquals(16, $instance->total);
        $this->assertFalse($instance->isDirty());
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['nullable_date' => 'datetime'];
}

class TestModel2 extends Model
{
    public $table = 'test_model2';
    public $timestamps = false;
    protected $guarded = [];
}

class Product extends Model
{
    use HasGeneratedColumns;

    protected $fillable = ['price'];
    public $timestamps = false;
};
