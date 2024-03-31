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
            $table->string('column');
        });
    }

    public function testItBasic()
    {
        $one = Model1::create();
        $one->twos()->Create(['column' => 'value']);

        $results = Model1::withAttribute('twos', 'column');

        $this->assertEquals([
            ['id' => 1, 'twos_attribute_column' => 'value'],
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
