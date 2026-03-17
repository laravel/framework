<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LogicException;

class EloquentToggleTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase(): void
    {
        Schema::create('toggle_models', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->string('name')->nullable();
            $table->integer('counter')->default(0);
            $table->timestamps();
        });
    }

    public function testToggle()
    {
        $model = ToggleModel::create(['is_active' => false]);

        $result = $model->toggle('is_active');

        $this->assertSame($model, $result);
        $this->assertTrue($model->is_active);
        $this->assertFalse($model->isDirty('is_active'));

        $result = $model->toggle('is_active');
        $this->assertSame($model, $result);
        $this->assertFalse($model->is_active);
    }

    public function testToggleWithExtraColumns()
    {
        $model = ToggleModel::create(['is_active' => false, 'name' => 'foo']);

        $result = $model->toggle('is_active', ['name' => 'bar']);

        $this->assertSame($model, $result);
        $this->assertTrue($model->is_active);
        $this->assertEquals('bar', $model->name);
        $this->assertFalse($model->isDirty('is_active'));
        $this->assertTrue($model->isDirty('name'));
    }

    public function testToggleThrowsExceptionForNonBooleanColumn()
    {
        $model = ToggleModel::create(['counter' => 0]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Column counter must be cast to a boolean to be toggled.');

        $model->toggle('counter');
    }

    public function testToggleQuietly()
    {
        $model = ToggleModel::create(['is_active' => false]);

        $result = $model->toggleQuietly('is_active');

        $this->assertSame($model, $result);
        $this->assertTrue($model->is_active);
        $this->assertFalse($model->isDirty('is_active'));
    }

    public function testToggleOnBuilder()
    {
        ToggleModel::create(['is_active' => false]);
        ToggleModel::create(['is_active' => true]);

        ToggleModel::query()->toggle('is_active');

        $models = ToggleModel::orderBy('id')->get();
        $this->assertTrue($models[0]->is_active);
        $this->assertFalse($models[1]->is_active);
    }

    public function testMassToggleWithExtraColumns()
    {
        ToggleModel::create(['is_active' => false, 'name' => 'foo']);
        ToggleModel::create(['is_active' => true, 'name' => 'bar']);

        ToggleModel::query()->toggle('is_active', ['name' => 'baz']);

        $models = ToggleModel::orderBy('id')->get();
        $this->assertTrue($models[0]->is_active);
        $this->assertEquals('baz', $models[0]->name);
        $this->assertFalse($models[1]->is_active);
        $this->assertEquals('baz', $models[1]->name);
    }

    public function testToggleOnBaseBuilder()
    {
        ToggleModel::create(['is_active' => false]);
        ToggleModel::create(['is_active' => true]);

        $this->getConnection()->table('toggle_models')->toggle('is_active');

        $models = ToggleModel::orderBy('id')->get();
        $this->assertTrue($models[0]->is_active);
        $this->assertFalse($models[1]->is_active);
    }

    public function testMassToggleManyRecords()
    {
        for ($i = 0; $i < 50; $i++) {
            ToggleModel::create(['is_active' => $i % 2 === 0]);
        }

        ToggleModel::query()->toggle('is_active');

        $models = ToggleModel::orderBy('id')->get();
        for ($i = 0; $i < 50; $i++) {
            $this->assertEquals($i % 2 !== 0, $models[$i]->is_active, "Record $i failed to toggle");
        }
    }

    public function testToggleReturningModelOnCancellation()
    {
        $model = ToggleModel::create(['is_active' => false]);

        ToggleModel::updating(function () {
            return false;
        });

        $result = $model->toggle('is_active');

        $this->assertSame($model, $result);
        $this->assertTrue($model->is_active); // Local attribute changed, but DB update cancelled
        $this->assertTrue($model->isDirty('is_active')); // It's still dirty because update failed/was cancelled
    }
}

class ToggleModel extends Model
{
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
