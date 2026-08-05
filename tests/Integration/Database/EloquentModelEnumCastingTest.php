<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ValueError;

include_once 'Enums.php';

class EloquentModelEnumCastingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('enum_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('string_status', 100)->nullable();
            $table->json('string_status_collection')->nullable();
            $table->json('string_status_array')->nullable();
            $table->integer('integer_status')->nullable();
            $table->json('integer_status_collection')->nullable();
            $table->json('integer_status_array')->nullable();
            $table->string('arrayable_status')->nullable();
        });

        Schema::create('unique_enum_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('string_status', 100)->unique();
        });
    }

    public function testEnumsAreCastable()
    {
        DB::table('enum_casts')->insert([
            'string_status' => 'pending',
            'string_status_collection' => json_encode(['pending', 'done']),
            'string_status_array' => json_encode(['pending', 'done']),
            'integer_status' => 1,
            'integer_status_collection' => json_encode([1, 2]),
            'integer_status_array' => json_encode([1, 2]),
            'arrayable_status' => 'pending',
        ]);

        $model = EloquentModelEnumCastingTestModel::first();

        $this->assertEquals(StringStatus::pending, $model->string_status);
        $this->assertEquals([StringStatus::pending, StringStatus::done], $model->string_status_collection->all());
        $this->assertEquals([StringStatus::pending, StringStatus::done], $model->string_status_array->toArray());
        $this->assertEquals(IntegerStatus::pending, $model->integer_status);
        $this->assertEquals([IntegerStatus::pending, IntegerStatus::done], $model->integer_status_collection->all());
        $this->assertEquals([IntegerStatus::pending, IntegerStatus::done], $model->integer_status_array->toArray());
        $this->assertEquals(ArrayableStatus::pending, $model->arrayable_status);
    }

    public function testEnumsReturnNullWhenNull()
    {
        DB::table('enum_casts')->insert([
            'string_status' => null,
            'string_status_collection' => null,
            'string_status_array' => null,
            'integer_status' => null,
            'integer_status_collection' => null,
            'integer_status_array' => null,
            'arrayable_status' => null,
        ]);

        $model = EloquentModelEnumCastingTestModel::first();

        $this->assertEquals(null, $model->string_status);
        $this->assertEquals(null, $model->string_status_collection);
        $this->assertEquals(null, $model->string_status_array);
        $this->assertEquals(null, $model->integer_status);
        $this->assertEquals(null, $model->integer_status_collection);
        $this->assertEquals(null, $model->integer_status_array);
        $this->assertEquals(null, $model->arrayable_status);
    }

    public function testEnumsAreCastableToArray()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => StringStatus::pending,
            'string_status_collection' => [StringStatus::pending, StringStatus::done],
            'string_status_array' => [StringStatus::pending, StringStatus::done],
            'integer_status' => IntegerStatus::pending,
            'integer_status_collection' => [IntegerStatus::pending, IntegerStatus::done],
            'integer_status_array' => [IntegerStatus::pending, IntegerStatus::done],
            'arrayable_status' => ArrayableStatus::pending,
        ]);

        $this->assertEquals([
            'string_status' => 'pending',
            'string_status_collection' => ['pending', 'done'],
            'string_status_array' => ['pending', 'done'],
            'integer_status' => 1,
            'integer_status_collection' => [1, 2],
            'integer_status_array' => [1, 2],
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
            'string_status_collection' => null,
            'string_status_array' => null,
            'integer_status' => null,
            'integer_status_collection' => null,
            'integer_status_array' => null,
            'arrayable_status' => null,
        ]);

        $this->assertEquals([
            'string_status' => null,
            'string_status_collection' => null,
            'string_status_array' => null,
            'integer_status' => null,
            'integer_status_collection' => null,
            'integer_status_array' => null,
            'arrayable_status' => null,
        ], $model->toArray());
    }

    public function testEnumsAreConvertedOnSave()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => StringStatus::pending,
            'string_status_collection' => [StringStatus::pending, StringStatus::done],
            'string_status_array' => [StringStatus::pending, StringStatus::done],
            'integer_status' => IntegerStatus::pending,
            'integer_status_collection' => [IntegerStatus::pending, IntegerStatus::done],
            'integer_status_array' => [IntegerStatus::pending, IntegerStatus::done],
            'arrayable_status' => ArrayableStatus::pending,
        ]);

        $model->save();

        $this->assertEquals([
            'id' => $model->id,
            'string_status' => 'pending',
            'string_status_collection' => json_encode(['pending', 'done']),
            'string_status_array' => json_encode(['pending', 'done']),
            'integer_status' => 1,
            'integer_status_collection' => json_encode([1, 2]),
            'integer_status_array' => json_encode([1, 2]),
            'arrayable_status' => 'pending',
        ], collect(DB::table('enum_casts')->where('id', $model->id)->first())->map(function ($value) {
            return str_replace(', ', ',', $value);
        })->all());
    }

    public function testEnumsAreNotConvertedOnSaveWhenAlreadyCorrect()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => 'pending',
            'string_status_collection' => ['pending', 'done'],
            'string_status_array' => ['pending', 'done'],
            'integer_status' => 1,
            'integer_status_collection' => [1, 2],
            'integer_status_array' => [1, 2],
            'arrayable_status' => 'pending',
        ]);

        $model->save();

        $this->assertEquals([
            'id' => $model->id,
            'string_status' => 'pending',
            'string_status_collection' => json_encode(['pending', 'done']),
            'string_status_array' => json_encode(['pending', 'done']),
            'integer_status' => 1,
            'integer_status_collection' => json_encode([1, 2]),
            'integer_status_array' => json_encode([1, 2]),
            'arrayable_status' => 'pending',
        ], collect(DB::table('enum_casts')->where('id', $model->id)->first())->map(function ($value) {
            return str_replace(', ', ',', $value);
        })->all());
    }

    public function testEnumsAcceptNullOnSave()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => null,
            'string_status_collection' => null,
            'string_status_array' => null,
            'integer_status' => null,
            'integer_status_collection' => null,
            'integer_status_array' => null,
            'arrayable_status' => null,
        ]);

        $model->save();

        $this->assertEquals((object) [
            'id' => $model->id,
            'string_status' => null,
            'string_status_collection' => null,
            'string_status_array' => null,
            'integer_status' => null,
            'integer_status_collection' => null,
            'integer_status_array' => null,
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

    public function testAttributeCastToAnEnumCanNotBeSetToAnotherEnum(): void
    {
        $model = new EloquentModelEnumCastingTestModel;

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage(
            sprintf('Value [%s] is not of the expected enum type [%s].', var_export(ArrayableStatus::pending, true), StringStatus::class)
        );

        $model->string_status = ArrayableStatus::pending;
    }

    public function testAttributeCastToAnEnumCanNotBeSetToAValueNotDefinedOnTheEnum(): void
    {
        $model = new EloquentModelEnumCastingTestModel;

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage(
            sprintf('"unexpected_value" is not a valid backing value for enum %s', StringStatus::class)
        );

        $model->string_status = 'unexpected_value';
    }

    public function testAnAttributeWithoutACastCanBeSetToAnEnum(): void
    {
        $model = new EloquentModelEnumCastingTestModel;

        $model->non_enum_status = StringStatus::pending;

        $this->assertEquals(StringStatus::pending, $model->non_enum_status);
    }

    public function testCreateOrFirst()
    {
        $model1 = EloquentModelEnumCastingUniqueTestModel::createOrFirst([
            'string_status' => StringStatus::pending,
        ]);

        $model2 = EloquentModelEnumCastingUniqueTestModel::createOrFirst([
            'string_status' => StringStatus::pending,
        ]);

        $model3 = EloquentModelEnumCastingUniqueTestModel::createOrFirst([
            'string_status' => StringStatus::done,
        ]);

        $this->assertEquals(StringStatus::pending, $model1->string_status);
        $this->assertEquals(StringStatus::pending, $model2->string_status);
        $this->assertTrue($model1->is($model2));
        $this->assertEquals(StringStatus::done, $model3->string_status);
    }
}

class EloquentModelEnumCastingTestModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'enum_casts';

    public $casts = [
        'string_status' => StringStatus::class,
        'string_status_collection' => AsEnumCollection::class.':'.StringStatus::class,
        'string_status_array' => AsEnumArrayObject::class.':'.StringStatus::class,
        'integer_status' => IntegerStatus::class,
        'integer_status_collection' => AsEnumCollection::class.':'.IntegerStatus::class,
        'integer_status_array' => AsEnumArrayObject::class.':'.IntegerStatus::class,
        'arrayable_status' => ArrayableStatus::class,
    ];
}

class EloquentModelEnumCastingUniqueTestModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'unique_enum_casts';

    public $casts = [
        'string_status' => StringStatus::class,
    ];
}
