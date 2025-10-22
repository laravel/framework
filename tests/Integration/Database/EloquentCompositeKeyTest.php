<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LogicException;

class EloquentCompositeKeyTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('composite_key_models', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->primary(['order_id', 'product_id']);
        });
    }

    public function testHasCompositeKey()
    {
        $model = new CompositeKeyModel;

        $this->assertTrue($model->hasCompositeKey());
        $this->assertEquals(['order_id', 'product_id'], $model->getKeyName());
    }

    public function testGetKeyReturnsArrayForCompositeKey()
    {
        $model = new CompositeKeyModel;
        $model->order_id = 1;
        $model->product_id = 5;
        $model->quantity = 10;

        $this->assertEquals(['order_id' => 1, 'product_id' => 5], $model->getKey());
    }

    public function testGetQualifiedKeyNameReturnsArray()
    {
        $model = new CompositeKeyModel;

        $qualifiedKeys = $model->getQualifiedKeyName();

        $this->assertIsArray($qualifiedKeys);
        $this->assertEquals([
            'composite_key_models.order_id',
            'composite_key_models.product_id',
        ], $qualifiedKeys);
    }

    public function testCreateWithCompositeKey()
    {
        $model = CompositeKeyModel::create([
            'order_id' => 1,
            'product_id' => 5,
            'quantity' => 10,
            'notes' => 'Test item',
        ]);

        $this->assertNotNull($model);
        $this->assertEquals(1, $model->order_id);
        $this->assertEquals(5, $model->product_id);
        $this->assertEquals(10, $model->quantity);
        $this->assertEquals('Test item', $model->notes);

        $this->assertDatabaseHas('composite_key_models', [
            'order_id' => 1,
            'product_id' => 5,
            'quantity' => 10,
            'notes' => 'Test item',
        ]);
    }

    public function testFindWithCompositeKey()
    {
        CompositeKeyModel::create([
            'order_id' => 1,
            'product_id' => 5,
            'quantity' => 10,
        ]);

        $found = CompositeKeyModel::find(['order_id' => 1, 'product_id' => 5]);

        $this->assertNotNull($found);
        $this->assertEquals(1, $found->order_id);
        $this->assertEquals(5, $found->product_id);
        $this->assertEquals(10, $found->quantity);
    }

    public function testFindReturnsNullWhenNotFound()
    {
        $found = CompositeKeyModel::find(['order_id' => 999, 'product_id' => 999]);

        $this->assertNull($found);
    }

    public function testFindOrFailWithCompositeKey()
    {
        CompositeKeyModel::create([
            'order_id' => 1,
            'product_id' => 5,
            'quantity' => 10,
        ]);

        $found = CompositeKeyModel::findOrFail(['order_id' => 1, 'product_id' => 5]);

        $this->assertNotNull($found);
        $this->assertEquals(1, $found->order_id);
        $this->assertEquals(5, $found->product_id);
    }

    public function testFindOrFailThrowsExceptionWhenNotFound()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        CompositeKeyModel::findOrFail(['order_id' => 999, 'product_id' => 999]);
    }

    public function testFindManyWithCompositeKeys()
    {
        CompositeKeyModel::create(['order_id' => 1, 'product_id' => 5, 'quantity' => 10]);
        CompositeKeyModel::create(['order_id' => 2, 'product_id' => 3, 'quantity' => 20]);
        CompositeKeyModel::create(['order_id' => 3, 'product_id' => 7, 'quantity' => 30]);

        $found = CompositeKeyModel::findMany([
            ['order_id' => 1, 'product_id' => 5],
            ['order_id' => 3, 'product_id' => 7],
        ]);

        $this->assertCount(2, $found);
        $this->assertEquals(10, $found->firstWhere('order_id', 1)->quantity);
        $this->assertEquals(30, $found->firstWhere('order_id', 3)->quantity);
    }

    public function testUpdateWithCompositeKey()
    {
        $model = CompositeKeyModel::create([
            'order_id' => 1,
            'product_id' => 5,
            'quantity' => 10,
            'notes' => 'Original',
        ]);

        $model->quantity = 20;
        $model->notes = 'Updated';
        $model->save();

        $this->assertDatabaseHas('composite_key_models', [
            'order_id' => 1,
            'product_id' => 5,
            'quantity' => 20,
            'notes' => 'Updated',
        ]);

        $this->assertDatabaseMissing('composite_key_models', [
            'order_id' => 1,
            'product_id' => 5,
            'notes' => 'Original',
        ]);
    }

    public function testDeleteWithCompositeKey()
    {
        $model = CompositeKeyModel::create([
            'order_id' => 1,
            'product_id' => 5,
            'quantity' => 10,
        ]);

        $model->delete();

        $this->assertDatabaseMissing('composite_key_models', [
            'order_id' => 1,
            'product_id' => 5,
        ]);
    }

    public function testWhereKeyWithCompositeKey()
    {
        CompositeKeyModel::create(['order_id' => 1, 'product_id' => 5, 'quantity' => 10]);
        CompositeKeyModel::create(['order_id' => 2, 'product_id' => 3, 'quantity' => 20]);

        $found = CompositeKeyModel::whereKey(['order_id' => 1, 'product_id' => 5])->first();

        $this->assertNotNull($found);
        $this->assertEquals(10, $found->quantity);
    }

    public function testWhereKeyNotWithCompositeKey()
    {
        CompositeKeyModel::create(['order_id' => 1, 'product_id' => 5, 'quantity' => 10]);
        CompositeKeyModel::create(['order_id' => 2, 'product_id' => 3, 'quantity' => 20]);

        $found = CompositeKeyModel::whereKeyNot(['order_id' => 1, 'product_id' => 5])->get();

        $this->assertCount(1, $found);
        $this->assertEquals(20, $found->first()->quantity);
    }

    public function testCompositeKeyCannotBeAutoIncrementing()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Composite primary keys cannot be auto-incrementing.');

        $model = new InvalidCompositeKeyModel;
        $model->order_id = 1;
        $model->product_id = 5;
        $model->save();
    }

    public function testWhereKeyThrowsExceptionForMissingKeyComponent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing value for composite key component: product_id');

        CompositeKeyModel::whereKey(['order_id' => 1])->get();
    }

    public function testUpdateChangesOnlySpecifiedModel()
    {
        CompositeKeyModel::create(['order_id' => 1, 'product_id' => 5, 'quantity' => 10]);
        CompositeKeyModel::create(['order_id' => 1, 'product_id' => 6, 'quantity' => 20]);

        $model = CompositeKeyModel::find(['order_id' => 1, 'product_id' => 5]);
        $model->quantity = 100;
        $model->save();

        $this->assertDatabaseHas('composite_key_models', [
            'order_id' => 1,
            'product_id' => 5,
            'quantity' => 100,
        ]);

        $this->assertDatabaseHas('composite_key_models', [
            'order_id' => 1,
            'product_id' => 6,
            'quantity' => 20,
        ]);
    }

    public function testFreshRetrievesCorrectModel()
    {
        $model = CompositeKeyModel::create(['order_id' => 1, 'product_id' => 5, 'quantity' => 10]);

        CompositeKeyModel::where('order_id', 1)
            ->where('product_id', 5)
            ->update(['quantity' => 50]);

        $fresh = $model->fresh();

        $this->assertEquals(50, $fresh->quantity);
    }

    public function testRefreshUpdatesModel()
    {
        $model = CompositeKeyModel::create(['order_id' => 1, 'product_id' => 5, 'quantity' => 10]);

        CompositeKeyModel::where('order_id', 1)
            ->where('product_id', 5)
            ->update(['quantity' => 50]);

        $model->refresh();

        $this->assertEquals(50, $model->quantity);
    }
}

class CompositeKeyModel extends Model
{
    protected $table = 'composite_key_models';

    protected $primaryKey = ['order_id', 'product_id'];

    public $incrementing = false;

    protected $guarded = [];
}

class InvalidCompositeKeyModel extends Model
{
    protected $table = 'composite_key_models';

    protected $primaryKey = ['order_id', 'product_id'];

    // Intentionally left as true to test validation
    public $incrementing = true;

    protected $guarded = [];
}
