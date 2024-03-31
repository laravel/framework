<?php

namespace Illuminate\Tests\Integration\Database\EloquentwithAttributeTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentWithAttributeTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('one', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('two', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
            $table->string('column_name');
        });
    }

    public function testItBasic()
    {
        $one = Model1::create();
        $one->twos()->create(['column_name' => 'value']);

        $results = Model1::withAttribute('twos', 'column_name');

        $this->assertEquals([
            ['id' => 1, 'twos_attribute_column_name' => 'value'],
        ], $results->get()->toArray());
    }

    public function testSorting()
    {
        $one = Model1::create();
        $one->twos()->createMany([
            ['column_name' => 'value1'],
            ['column_name' => 'value2'],
            ['column_name' => 'value3'],
            ['column_name' => 'value4'],
        ]);

        $results = Model1::withAttribute(['twos' => fn ($q) => $q->latest('id')], 'column_name');

        $this->assertEquals([
            ['id' => 4, 'twos_attribute_column_name' => 'value4'],
        ], $results->get()->toArray());
    }
}

class Model1 extends Model
{
    public $table = 'one';
    public $timestamps = false;
    protected $guarded = [];

    public function twos()
    {
        return $this->hasMany(Model2::class, 'one_id');
    }
}

class Model2 extends Model
{
    public $table = 'two';
    public $timestamps = false;
    protected $guarded = [];
}
