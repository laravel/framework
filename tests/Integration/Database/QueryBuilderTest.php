<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueryBuilderTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('content');
            $table->timestamp('created_at');
        });

        DB::table('posts')->insert([
            ['title' => 'Foo Post', 'content' => 'Lorem Ipsum.', 'created_at' => new Carbon('2017-11-12 13:14:15')],
            ['title' => 'Bar Post', 'content' => 'Lorem Ipsum.', 'created_at' => new Carbon('2018-01-02 03:04:05')],
        ]);
    }

    public function testSole()
    {
        $expected = ['id' => '1', 'title' => 'Foo Post'];

        $this->assertEquals($expected, (array) DB::table('posts')->where('title', 'Foo Post')->select('id', 'title')->sole());
    }

    public function testSoleFailsForMultipleRecords()
    {
        DB::table('posts')->insert([
            ['title' => 'Foo Post', 'content' => 'Lorem Ipsum.', 'created_at' => new Carbon('2017-11-12 13:14:15')],
        ]);

        $this->expectException(MultipleRecordsFoundException::class);

        DB::table('posts')->where('title', 'Foo Post')->sole();
    }

    public function testSoleFailsIfNoRecords()
    {
        $this->expectException(RecordsNotFoundException::class);

        DB::table('posts')->where('title', 'Baz Post')->sole();
    }

    public function testSelect()
    {
        $expected = ['id' => '1', 'title' => 'Foo Post'];

        $this->assertEquals($expected, (array) DB::table('posts')->select('id', 'title')->first());
        $this->assertEquals($expected, (array) DB::table('posts')->select(['id', 'title'])->first());
    }

    public function testSelectReplacesExistingSelects()
    {
        $this->assertEquals(
            ['id' => '1', 'title' => 'Foo Post'],
            (array) DB::table('posts')->select('content')->select(['id', 'title'])->first()
        );
    }

    public function testSelectWithSubQuery()
    {
        $this->assertEquals(
            ['id' => '1', 'title' => 'Foo Post', 'foo' => 'Lorem Ipsum.'],
            (array) DB::table('posts')->select(['id', 'title', 'foo' => function ($query) {
                $query->select('content');
            }])->first()
        );
    }

    public function testAddSelect()
    {
        $expected = ['id' => '1', 'title' => 'Foo Post', 'content' => 'Lorem Ipsum.'];

        $this->assertEquals($expected, (array) DB::table('posts')->select('id')->addSelect('title', 'content')->first());
        $this->assertEquals($expected, (array) DB::table('posts')->select('id')->addSelect(['title', 'content'])->first());
        $this->assertEquals($expected, (array) DB::table('posts')->addSelect(['id', 'title', 'content'])->first());
    }

    public function testAddSelectWithSubQuery()
    {
        $this->assertEquals(
            ['id' => '1', 'title' => 'Foo Post', 'foo' => 'Lorem Ipsum.'],
            (array) DB::table('posts')->addSelect(['id', 'title', 'foo' => function ($query) {
                $query->select('content');
            }])->first()
        );
    }

    public function testFromWithAlias()
    {
        $this->assertCount(2, DB::table('posts', 'alias')->select('alias.*')->get());
    }

    public function testFromWithSubQuery()
    {
        $this->assertSame(
            'Fake Post',
            DB::table(function ($query) {
                $query->selectRaw("'Fake Post' as title");
            }, 'posts')->first()->title
        );
    }

    public function testWhereValueSubQuery()
    {
        $subQuery = function ($query) {
            $query->selectRaw("'Sub query value'");
        };

        $this->assertTrue(DB::table('posts')->where($subQuery, 'Sub query value')->exists());
        $this->assertFalse(DB::table('posts')->where($subQuery, 'Does not match')->exists());
        $this->assertTrue(DB::table('posts')->where($subQuery, '!=', 'Does not match')->exists());
    }

    public function testWhereValueSubQueryBuilder()
    {
        $subQuery = DB::table('posts')->selectRaw("'Sub query value'")->limit(1);

        $this->assertTrue(DB::table('posts')->where($subQuery, 'Sub query value')->exists());
        $this->assertFalse(DB::table('posts')->where($subQuery, 'Does not match')->exists());
        $this->assertTrue(DB::table('posts')->where($subQuery, '!=', 'Does not match')->exists());
    }

    public function testWhereDate()
    {
        $this->assertSame(1, DB::table('posts')->whereDate('created_at', '2018-01-02')->count());
        $this->assertSame(1, DB::table('posts')->whereDate('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testOrWhereDate()
    {
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereDate('created_at', '2018-01-02')->count());
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereDate('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testWhereDay()
    {
        $this->assertSame(1, DB::table('posts')->whereDay('created_at', '02')->count());
        $this->assertSame(1, DB::table('posts')->whereDay('created_at', 2)->count());
        $this->assertSame(1, DB::table('posts')->whereDay('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testOrWhereDay()
    {
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereDay('created_at', '02')->count());
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereDay('created_at', 2)->count());
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereDay('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testWhereMonth()
    {
        $this->assertSame(1, DB::table('posts')->whereMonth('created_at', '01')->count());
        $this->assertSame(1, DB::table('posts')->whereMonth('created_at', 1)->count());
        $this->assertSame(1, DB::table('posts')->whereMonth('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testOrWhereMonth()
    {
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereMonth('created_at', '01')->count());
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereMonth('created_at', 1)->count());
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereMonth('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testWhereYear()
    {
        $this->assertSame(1, DB::table('posts')->whereYear('created_at', '2018')->count());
        $this->assertSame(1, DB::table('posts')->whereYear('created_at', 2018)->count());
        $this->assertSame(1, DB::table('posts')->whereYear('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testOrWhereYear()
    {
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereYear('created_at', '2018')->count());
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereYear('created_at', 2018)->count());
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereYear('created_at', new Carbon('2018-01-02'))->count());
    }

    public function testWhereTime()
    {
        $this->assertSame(1, DB::table('posts')->whereTime('created_at', '03:04:05')->count());
        $this->assertSame(1, DB::table('posts')->whereTime('created_at', new Carbon('2018-01-02 03:04:05'))->count());
    }

    public function testOrWhereTime()
    {
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereTime('created_at', '03:04:05')->count());
        $this->assertSame(2, DB::table('posts')->where('id', 1)->orWhereTime('created_at', new Carbon('2018-01-02 03:04:05'))->count());
    }

    public function testPaginateWithSpecificColumns()
    {
        $result = DB::table('posts')->paginate(5, ['title', 'content']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals($result->items(), [
            (object) ['title' => 'Foo Post', 'content' => 'Lorem Ipsum.'],
            (object) ['title' => 'Bar Post', 'content' => 'Lorem Ipsum.'],
        ]);
    }

    public function testChunkMap()
    {
        DB::enableQueryLog();

        $results = DB::table('posts')->orderBy('id')->chunkMap(function ($post) {
            return $post->title;
        }, 1);

        $this->assertCount(2, $results);
        $this->assertSame('Foo Post', $results[0]);
        $this->assertSame('Bar Post', $results[1]);
        $this->assertCount(3, DB::getQueryLog());
    }
}
