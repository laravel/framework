<?php

namespace Illuminate\Tests\Integration\Database\EloquentWithAvgTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentWithAvgTest extends DatabaseTestCase
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
            $table->integer('price');
        });
    }

    /**
     * @test
     */
    public function it_basic()
    {
        $one = Model1::create();
        Model2::insert([
                            ['one_id' => $one->id , 'price' =>10],
                            ['one_id' => $one->id , 'price' =>20],
                            ['one_id' => $one->id , 'price' =>30],
                        ]);
        $results = Model1::withAvg('twos', 'price');

        $this->assertEquals([
            ['id' => 1, 'price_avg' => 20],
        ], $results->get()->toArray());
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