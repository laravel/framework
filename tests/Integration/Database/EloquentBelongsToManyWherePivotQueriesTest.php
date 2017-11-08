<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToManyWherePivotQueriesTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class EloquentBelongsToManyWherePivotQueriesTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('posts', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->string('flag')->default('');
            $table->timestamps();
        });

        Schema::create('tags', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts_tags', function ($table) {
            $table->integer('post_id');
            $table->integer('tag_id');
            $table->string('flag')->default('');
            $table->timestamps();
        });

        Carbon::setTestNow(null);
    }

    /**
     * @test
     */
    public function basic_create_and_retrieve()
    {
        Carbon::setTestNow(
            Carbon::createFromFormat('Y-m-d H:i:s', '2017-10-10 10:10:10')
        );

        $post = Post::create(['title' => str_random()]);

        $tag = Tag::create(['name' => str_random()]);
        $tag2 = Tag::create(['name' => str_random()]);

        $post->tags()->sync([
            $tag->id => ['flag' => 'taylor'],
            $tag2->id => ['flag' => 'mohamed'],
        ]);

        $results = $post->tags()->wherePivot(function($q){
            return $q->wherePivot('flag', 'taylor');
        })->first();

        $this->assertEquals($results->id, $tag->id);
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = ['id'];

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_id')
            ->withPivot('flag');
    }
}

class Tag extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $guarded = ['id'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'posts_tags', 'tag_id', 'post_id');
    }
}
