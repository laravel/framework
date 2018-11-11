<?php

namespace Illuminate\Tests\Integration\Database\EloquentWithAggregateTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentWithAggregateTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('one', function ($table) {
            $table->increments('id');
        });

        Schema::create('two', function ($table) {
            $table->increments('id');
            $table->integer('one_id');
            $table->integer('quantity');
        });

        Schema::create('three', function ($table) {
            $table->increments('id');
        });

        Schema::create('four', function ($table) {
            $table->increments('id');
            $table->integer('three_id');
            $table->integer('quantity');
        });
    }

    public function testSingleColumn()
    {
        $one = Model1::create();
        $one->twos()->createMany([
            ['quantity' => 3],
            ['quantity' => 4],
            ['quantity' => 2],
        ]);

        $result = Model1::withSum('twos:quantity')->first();

        $this->assertEquals(9, $result->twos_quantity_sum);
    }

    public function testMultipleColumns()
    {
        $one = Model1::create();
        $one->twos()->createMany([
            ['quantity' => 8],
            ['quantity' => 1],
        ]);

        $result = Model1::withMax('twos:quantity', 'twos:id')->first();

        $this->assertEquals(8, $result->twos_quantity_max);
        $this->assertEquals(2, $result->twos_id_max);
    }

    public function testWithConstraintsAndAlias()
    {
        $one = Model1::create();
        $one->twos()->createMany([
            ['quantity' => 3],
            ['quantity' => 1],
            ['quantity' => 0],
            ['quantity' => 5],
            ['quantity' => 1],
        ]);

        $result = Model1::withAvg(['twos:quantity as avg_quantity' => function ($q) {
            $q->where('quantity', '>', 2);
        }])->first();

        $this->assertEquals(4, $result->avg_quantity);
    }

    public function testAttributeEagerLoading()
    {
        $three = Model3::create();
        $three->fours()->createMany([
            ['quantity' => 5],
            ['quantity' => 3],
            ['quantity' => 1],
        ]);

        $result = Model3::first();

        $this->assertEquals([
            'id' => 1,
            'fours_quantity_avg' => 3,
            'fours_quantity_max' => 5,
            'fours_id_max' => 3,
        ], $result->toArray());
    }
}

class Model1 extends Model
{
    public $table = 'one';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function twos()
    {
        return $this->hasMany(Model2::class, 'one_id');
    }
}

class Model2 extends Model
{
    public $table = 'two';
    public $timestamps = false;
    protected $guarded = ['id'];
}

class Model3 extends Model
{
    public $table = 'three';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $withAggregates = [
        'avg' => 'fours:quantity',
        'max' => [
            'fours:quantity',
            'fours:id',
        ],
    ];

    public function fours()
    {
        return $this->hasMany(Model4::class, 'three_id');
    }
}

class Model4 extends Model
{
    public $table = 'four';
    public $timestamps = false;
    protected $guarded = ['id'];
}