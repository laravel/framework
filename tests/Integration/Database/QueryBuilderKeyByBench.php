<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ONLY FOR SHOWCASE PURPOSES. TO BE REMOVED.
 */
class QueryBuilderKeyByBench extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('content');
            $table->timestamp('created_at');
        });

        $data = [];
        $fake = fake();

        // Define a random spot to have a fixed title so we can search for it.
        $loc = random_int(0, 999);

        for ($i = 0; $i < 1000; $i++) {
            $data[] = [
                'title' => $i === $loc ? 'Foo Post' : $fake->title,
                'content' => $fake->text(100),
                'created_at' => new Carbon($fake->date('Y-m-d H:i:s'))
            ];
        }

        DB::table('posts')->insert($data);
    }

    public function testBenchWithKey()
    {
        dump(Benchmark::measure(function () {
            $results = DB::table('posts')->keyBy('title')->get();

            $found = $results['Foo Post'];
        }, 100));
    }

    public function testBenchWithoutKey()
    {
        dump(Benchmark::measure(function () {
            $results = DB::table('posts')->get()->keyBy('title');

            $found = $results['Foo Post'];
        }, 100));
    }
}
