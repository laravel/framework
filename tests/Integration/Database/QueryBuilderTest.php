<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToManyTest;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class QueryBuilderTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function ($table) {
            $table->timestamp('created_at');
        });

        DB::table('posts')->insert([
            ['created_at' => new Carbon('2017-11-12 13:14:15')],
            ['created_at' => new Carbon('2018-01-02 03:04:05')],
        ]);
    }

    public function testWhereDate()
    {
        $this->assertSame(1, DB::table('posts')->whereDate('created_at', '2018-01-02')->count());
        $this->assertSame(1, DB::table('posts')->whereDate('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testWhereDay()
    {
        $this->assertSame(1, DB::table('posts')->whereDay('created_at', '02')->count());
        $this->assertSame(1, DB::table('posts')->whereDay('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testWhereMonth()
    {
        $this->assertSame(1, DB::table('posts')->whereMonth('created_at', '01')->count());
        $this->assertSame(1, DB::table('posts')->whereMonth('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testWhereYear()
    {
        $this->assertSame(1, DB::table('posts')->whereYear('created_at', '2018')->count());
        $this->assertSame(1, DB::table('posts')->whereYear('created_at', 2018)->count());
        $this->assertSame(1, DB::table('posts')->whereYear('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testWhereTime()
    {
        $this->assertSame(1, DB::table('posts')->whereTime('created_at', '03:04:05')->count());
        $this->assertSame(1, DB::table('posts')->whereTime('created_at', new Carbon('2018-01-02 03:04:05'))->count());
    }
}
