<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class EloquentCursorPaginateTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('test_users', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });
    }

    public function testCursorPaginationOnTopOfColumns()
    {
        for ($i = 1; $i <= 50; $i++) {
            TestPost::create([
                'title' => 'Title '.$i,
            ]);
        }

        $this->assertCount(15, TestPost::cursorPaginate(15, ['id', 'title']));
    }

    public function testPaginationWithDistinct()
    {
        for ($i = 1; $i <= 3; $i++) {
            TestPost::create(['title' => 'Hello world']);
            TestPost::create(['title' => 'Goodbye world']);
        }

        $query = TestPost::query()->distinct();

        $this->assertEquals(6, $query->get()->count());
        $this->assertEquals(6, $query->count());
        $this->assertCount(6, $query->cursorPaginate()->items());
    }

    public function testPaginationWithDistinctColumnsAndSelect()
    {
        for ($i = 1; $i <= 3; $i++) {
            TestPost::create(['title' => 'Hello world']);
            TestPost::create(['title' => 'Goodbye world']);
        }

        $query = TestPost::query()->distinct('title')->select('title');

        $this->assertEquals(2, $query->get()->count());
        $this->assertEquals(2, $query->count());
        $this->assertCount(2, $query->cursorPaginate()->items());
    }

    public function testPaginationWithDistinctColumnsAndSelectAndJoin()
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = TestUser::create();
            for ($j = 1; $j <= 10; $j++) {
                TestPost::create([
                    'title' => 'Title '.$i,
                    'user_id' => $user->id,
                ]);
            }
        }

        $query = TestUser::query()->join('test_posts', 'test_posts.user_id', '=', 'test_users.id')
            ->distinct('test_users.id')->select('test_users.*');

        $this->assertEquals(5, $query->get()->count());
        $this->assertEquals(5, $query->count());
        $this->assertCount(5, $query->cursorPaginate()->items());
    }
}

class TestPost extends Model
{
    protected $guarded = [];
}

class TestUser extends Model
{
    protected $guarded = [];
}
