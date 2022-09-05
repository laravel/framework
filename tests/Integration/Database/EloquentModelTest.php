<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentModelTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
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
            $table->timestamps();
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

    public function testWithoutTimestamp()
    {
        Carbon::setTestNow($now = Carbon::now()->setYear(1995)->startOfYear());
        $user = TestModel3::create(['name' => 'foo']);
        Carbon::setTestNow(Carbon::now()->addHour());

        $this->assertTrue($user->timestamps);

        $user->withoutTimestamps(fn () => $user->update([
            'name' => 'bar',
        ]));

        $this->assertTrue($user->timestamps);
        $this->assertTrue($now->equalTo($user->updated_at));
    }

    public function testWithoutTimestampWhenAlreadyIgnoringTimestamps()
    {
        Carbon::setTestNow($now = Carbon::now()->setYear(1995)->startOfYear());
        $user = TestModel3::create(['name' => 'foo']);
        Carbon::setTestNow(Carbon::now()->addHour());

        $user->timestamps = false;

        $user->withoutTimestamps(fn () => $user->update([
            'name' => 'bar',
        ]));

        $this->assertFalse($user->timestamps);
        $this->assertTrue($now->equalTo($user->updated_at));
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
    public $timestamps = true;
    protected $guarded = [];
}
