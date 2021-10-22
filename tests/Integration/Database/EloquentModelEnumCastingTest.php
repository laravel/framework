<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (strpos(PHP_VERSION, '8.1') === 0) {
    include 'Enums.php';
}

/**
 * @group integration
 */
class EloquentModelEnumCastingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('enum_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('string_status', 100)->nullable();
            $table->integer('integer_status')->nullable();
        });
    }

    /**
     * @requires PHP 8.1
     */
    public function testEnumsAreCastable()
    {
        DB::table('enum_casts')->insert([
            'string_status' => 'pending',
            'integer_status' => 1,
        ]);

        $model = EloquentModelEnumCastingTestModel::first();

        $this->assertEquals(StringStatus::pending, $model->string_status);
        $this->assertEquals(IntegerStatus::pending, $model->integer_status);

    }

    /**
     * @requires PHP 8.1
     */
    public function testEnumsAreCastableToArray()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => StringStatus::pending,
            'integer_status' => IntegerStatus::pending,
        ]);

        $this->assertEquals([
            'string_status' => 'pending',
            'integer_status' => 1,
        ], $model->toArray());
    }

    /**
     * @requires PHP 8.1
     */
    public function testEnumsAreConvertedOnSave()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => StringStatus::pending,
            'integer_status' => IntegerStatus::pending,
        ]);

        $model->save();

        $this->assertEquals((object) [
            'id' => $model->id,
            'string_status' => 'pending',
            'integer_status' => 1,
        ], DB::table('enum_casts')->where('id', $model->id)->first());
    }
}

/**
 * @property $secret
 * @property $secret_array
 * @property $secret_json
 * @property $secret_object
 * @property $secret_collection
 */
class EloquentModelEnumCastingTestModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'enum_casts';

    public $casts = [
        'string_status' => StringStatus::class,
        'integer_status' => IntegerStatus::class,
    ];
}
