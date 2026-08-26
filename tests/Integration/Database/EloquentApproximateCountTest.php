<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentApproximateCountTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('one', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('some_int');
        });
    }

    public function testItBasic()
    {
        // arrange:
        // inserts 1000 rows:
        $insert = [];
        for ($i = 0; $i < 1000; $i++) {
            $insert[] = ['some_int' => $i];
        }
        DB::table('one')->insert($insert);

        // act:
        $count = ModelApproximatedCount::query()->approximateCount();

        // assert:
        $this->assertIsInt($count);
        $this->assertGreaterThan(500, $count);
        $this->assertLessThan(3000, $count);

        $count = DB::table('one')->approximateCount();
        $this->assertIsInt($count);
        $this->assertGreaterThan(500, $count);
        $this->assertLessThan(3000, $count);
    }
}

class ModelApproximatedCount extends Model
{
    public $table = 'one';
    public $timestamps = false;
    protected $guarded = ['id'];
}
