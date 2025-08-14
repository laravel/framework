<?php

namespace Illuminate\Tests\Integration\Database;

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

        Schema::create('test_model3', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->json('data');
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
            'name' => $originalName = Str::random(), 'title' => Str::random(),
        ]);

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->name = $overrideName = Str::random();

        $this->assertEquals(['name' => $overrideName], $user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
        $this->assertTrue($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->save();

        $this->assertEmpty($user->getDirty());
        $this->assertEquals(['name' => $overrideName], $user->getChanges());
        $this->assertEquals(['name' => $originalName], $user->getPrevious());
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
        $this->assertEmpty($user->getPrevious());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->name = $overrideName = Str::random();

        $this->assertEquals(['name' => $overrideName], $user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
        $this->assertTrue($user->isDirty());
        $this->assertFalse($user->wasChanged());
        $this->assertSame($originalName, $user->getOriginal('name'));
        $this->assertSame($overrideName, $user->getAttribute('name'));

        $user->discardChanges();

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
        $this->assertSame($originalName, $user->getOriginal('name'));
        $this->assertSame($originalName, $user->getAttribute('name'));

        $user->save();
        $this->assertFalse($user->wasChanged());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
    }

    public function testGetChangesWithKey()
    {
        $user = TestModel2::create([
            'name' => $originalName = Str::random(),
            'title' => $originalTitle = Str::random(),
        ]);

        $user->name = $newName = Str::random();
        $user->title = $newTitle = Str::random();
        $user->save();

        $this->assertNull($user->getChanges('nonexistent'));
        $this->assertSame($newName, $user->getChanges('name'));
        $this->assertSame($newTitle, $user->getChanges('title'));
        $this->assertSame('default', $user->getChanges('nonexistent', 'default'));
        $this->assertEquals(['name' => $newName, 'title' => $newTitle], $user->getChanges());
    }

    public function testGetPreviousWithKey()
    {
        $user = TestModel2::create([
            'name' => $originalName = Str::random(),
            'title' => $originalTitle = Str::random(),
        ]);

        $user->name = $newName = Str::random();
        $user->title = $newTitle = Str::random();
        $user->save();

        $this->assertNull($user->getPrevious('nonexistent'));
        $this->assertSame($originalName, $user->getPrevious('name'));
        $this->assertSame($originalTitle, $user->getPrevious('title'));
        $this->assertSame('default', $user->getPrevious('nonexistent', 'default'));
        $this->assertEquals(['name' => $originalName, 'title' => $originalTitle], $user->getPrevious());
    }

    public function testGetChangesAndPreviousWithCasts()
    {
        $user = TestModel3::create([
            'name' => $originalName = Str::random(),
            'data' => $originalData = ['key' => 'value'],
        ]);

        $user->name = $newName = Str::random();
        $user->data = $newData = ['key' => 'new_value'];
        $user->save();

        $this->assertSame($newName, $user->getChanges('name'));
        $this->assertEquals($newData, $user->getChanges('data'));
        $this->assertSame($originalName, $user->getPrevious('name'));
        $this->assertEquals($originalData, $user->getPrevious('data'));
        $this->assertEquals(['name' => $newName, 'data' => $newData], $user->getChanges());
        $this->assertEquals(['name' => $originalName, 'data' => $originalData], $user->getPrevious());
    }

    public function testGetChangesAndPreviousWithNoChanges()
    {
        $user = TestModel2::create([
            'name' => $name = Str::random(),
            'title' => $title = Str::random(),
        ]);

        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
        $this->assertNull($user->getChanges('name'));
        $this->assertNull($user->getPrevious('name'));
        $this->assertSame('default', $user->getChanges('name', 'default'));
        $this->assertSame('default', $user->getPrevious('name', 'default'));
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

class TestModel3 extends Model
{
    public $table = 'test_model3';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['data' => 'array'];
}
