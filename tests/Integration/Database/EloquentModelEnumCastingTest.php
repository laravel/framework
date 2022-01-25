<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (PHP_VERSION_ID >= 80100) {
    include 'Enums.php';
}

/**
 * @requires PHP 8.1
 */
class EloquentModelEnumCastingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('enum_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('string_status', 100)->nullable();
            $table->integer('integer_status')->nullable();
            $table->string('arrayable_status')->nullable();
        });
    }

    public function testEnumsAreCastable()
    {
        DB::table('enum_casts')->insert([
            'string_status' => 'pending',
            'integer_status' => 1,
            'arrayable_status' => 'pending',
        ]);

        $model = EloquentModelEnumCastingTestModel::first();

        $this->assertEquals(StringStatus::pending, $model->string_status);
        $this->assertEquals(IntegerStatus::pending, $model->integer_status);
        $this->assertEquals(ArrayableStatus::pending, $model->arrayable_status);
    }

    public function testEnumsReturnNullWhenNull()
    {
        DB::table('enum_casts')->insert([
            'string_status' => null,
            'integer_status' => null,
            'arrayable_status' => null,
        ]);

        $model = EloquentModelEnumCastingTestModel::first();

        $this->assertEquals(null, $model->string_status);
        $this->assertEquals(null, $model->integer_status);
        $this->assertEquals(null, $model->arrayable_status);
    }

    public function testEnumsAreCastableToArray()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => StringStatus::pending,
            'integer_status' => IntegerStatus::pending,
            'arrayable_status' => ArrayableStatus::pending,
        ]);

        $this->assertEquals([
            'string_status' => 'pending',
            'integer_status' => 1,
            'arrayable_status' => [
                'name' => 'pending',
                'value' => 'pending',
                'description' => 'pending status description',
            ],
        ], $model->toArray());
    }

    public function testEnumsAreCastableToArrayWhenNull()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => null,
            'integer_status' => null,
            'arrayable_status' => null,
        ]);

        $this->assertEquals([
            'string_status' => null,
            'integer_status' => null,
            'arrayable_status' => null,
        ], $model->toArray());
    }

    public function testEnumsAreConvertedOnSave()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => StringStatus::pending,
            'integer_status' => IntegerStatus::pending,
            'arrayable_status' => ArrayableStatus::pending,
        ]);

        $model->save();

        $this->assertEquals((object) [
            'id' => $model->id,
            'string_status' => 'pending',
            'integer_status' => 1,
            'arrayable_status' => 'pending',
        ], DB::table('enum_casts')->where('id', $model->id)->first());
    }

    public function testEnumsAcceptNullOnSave()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => null,
            'integer_status' => null,
            'arrayable_status' => null,
        ]);

        $model->save();

        $this->assertEquals((object) [
            'id' => $model->id,
            'string_status' => null,
            'integer_status' => null,
            'arrayable_status' => null,
        ], DB::table('enum_casts')->where('id', $model->id)->first());
    }

    public function testEnumsAcceptBackedValueOnSave()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => 'pending',
            'integer_status' => 1,
            'arrayable_status' => 'pending',
        ]);

        $model->save();

        $model = EloquentModelEnumCastingTestModel::first();

        $this->assertEquals(StringStatus::pending, $model->string_status);
        $this->assertEquals(IntegerStatus::pending, $model->integer_status);
        $this->assertEquals(ArrayableStatus::pending, $model->arrayable_status);
    }

    public function testFirstOrNew()
    {
        DB::table('enum_casts')->insert([
            'string_status' => 'pending',
            'integer_status' => 1,
            'arrayable_status' => 'pending',
        ]);

        $model = EloquentModelEnumCastingTestModel::firstOrNew([
            'string_status' => StringStatus::pending,
        ]);

        $model2 = EloquentModelEnumCastingTestModel::firstOrNew([
            'string_status' => StringStatus::done,
        ]);

        $this->assertTrue($model->exists);
        $this->assertFalse($model2->exists);

        $model2->save();

        $this->assertEquals(StringStatus::done, $model2->string_status);
    }

    public function testFirstOrCreate()
    {
        DB::table('enum_casts')->insert([
            'string_status' => 'pending',
            'integer_status' => 1,
        ]);

        $model = EloquentModelEnumCastingTestModel::firstOrCreate([
            'string_status' => StringStatus::pending,
        ]);

        $model2 = EloquentModelEnumCastingTestModel::firstOrCreate([
            'string_status' => StringStatus::done,
        ]);

        $this->assertEquals(StringStatus::pending, $model->string_status);
        $this->assertEquals(StringStatus::done, $model2->string_status);
    }
}

class EloquentModelEnumCastingTestModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'enum_casts';

    public $casts = [
        'string_status' => StringStatus::class,
        'integer_status' => IntegerStatus::class,
        'arrayable_status' => ArrayableStatus::class,
    ];
}
