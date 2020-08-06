<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentModelTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('test_model1', function ($table) {
            $table->increments('id');
            $table->timestamp('nullable_date')->nullable();
        });

        Schema::create('test_model2', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
        });
    }

    public function test_cant_update_guarded_attributes_using_different_casing()
    {
        $model = new TestModel2;

        $model->fill(['ID' => 123]);

        $this->assertNull($model->ID);
    }

    public function test_cant_update_guarded_attribute_using_json()
    {
        $model = new TestModel2;

        $model->fill(['id->foo' => 123]);

        $this->assertNull($model->id);
    }

    public function test_cant_mass_fill_attributes_with_table_names_when_using_guarded()
    {
        $model = new TestModel2;

        $model->fill(['foo.bar' => 123]);

        $this->assertCount(0, $model->getAttributes());
    }

    public function test_user_can_update_nullable_date()
    {
        $user = TestModel1::create([
            'nullable_date' => null,
        ]);

        $user->fill([
            'nullable_date' => $now = \Illuminate\Support\Carbon::now(),
        ]);
        $this->assertTrue($user->isDirty('nullable_date'));

        $user->save();
        $this->assertEquals($now->toDateString(), $user->nullable_date->toDateString());
    }

    public function test_attribute_changes()
    {
        $user = TestModel2::create([
            'name' => str_random(), 'title' => str_random(),
        ]);

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->name = $name = str_random();

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
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['nullable_date'];
}

class TestModel2 extends Model
{
    public $table = 'test_model2';
    public $timestamps = false;
    protected $guarded = ['id'];
}
