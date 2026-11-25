<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseTruncationTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('body');
        });
    }

    public function testTruncateTables()
    {
        DB::table('posts')->insert([['title' => 'Laravel'], ['title' => 'Laravel']]);
        DB::table('comments')->insert(['body' => 'Laravel']);
        DB::table('comments')->delete();

        (new class($this->app)
        {
            use DatabaseTruncation {
                truncateTablesForAllConnections as public;
            }

            public function __construct(public $app)
            {
                //
            }
        })->truncateTablesForAllConnections();

        $this->assertSame(1, DB::table('posts')->insertGetId(['title' => 'Laravel']));
        $this->assertSame(2, DB::table('comments')->insertGetId(['body' => 'Laravel']));
    }
}
