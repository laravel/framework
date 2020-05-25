<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSqlServerIdentityInsertTest extends DatabaseSqlServerTestCase
{
    public function testIdentityInsertsGetsId()
    {
        Schema::create('increment_test', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bar')->nullable();
        });

        $model = new Model1(['bar'=> 'test']);
        $model->save();
        $this->assertEquals(1, $model->id);
    }

    public function testIdentityInsertReturnsRightIdWhenTriggered()
    {
        Schema::create('increment_test', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bar')->nullable();
        });
        Schema::create('trigger_store', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bar')->nullable();
        });
        DB::statement('CREATE TRIGGER test ON increment_test AFTER INSERT AS insert into trigger_store (bar) values (\'dummy\')');

        $store = new Model1(['bar'=> 'test']);
        $store->table ='trigger_store';
        $store->save();

        $model = new Model1(['bar'=> 'test']);
        $model->save();
        $this->assertEquals(1, $model->id);
    }
}


class Model1 extends Model
{
    public $table = 'increment_test';
    public $timestamps = false;
    protected $fillable = ['bar'];
}
