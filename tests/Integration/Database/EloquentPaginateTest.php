<?php

namespace Illuminate\Tests\Integration\Database\EloquentPaginateTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentPaginateTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('posts', function ($table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }

    public function test_pagination_on_top_of_columns()
    {
        for ($i = 1; $i <= 50; $i++) {
            Post::create([
                'title' => 'Title ' . $i,
            ]);
        }

        $this->assertCount(15, Post::paginate(15, ['id', 'title']));
    }
}

class Post extends Model
{
    protected $guarded = [];
}
