<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToManyTest;

use Carbon\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentBelongsToManyTest extends TestCase
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
            $table->string('flag');
        });
    }

    public function test_can_retrieve_related_ids()
    {
        $post = Post::create(['title' => str_random()]);

        DB::table('tags')->insert([
            ['id' => 200, 'name' => 'excluded'],
            ['id' => 300, 'name' => str_random()],
        ]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => 200, 'flag' => ''],
            ['post_id' => $post->id, 'tag_id' => 300, 'flag' => 'exclude'],
            ['post_id' => $post->id, 'tag_id' => 400, 'flag' => ''],
        ]);

        $this->assertEquals([200, 400], $post->tags()->allRelatedIds()->toArray());
    }

    public function test_can_touch_related_models()
    {
        $post = Post::create(['title' => str_random()]);

        DB::table('tags')->insert([
            ['id' => 200, 'name' => str_random()],
            ['id' => 300, 'name' => str_random()],
        ]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => 200, 'flag' => ''],
            ['post_id' => $post->id, 'tag_id' => 300, 'flag' => 'exclude'],
            ['post_id' => $post->id, 'tag_id' => 400, 'flag' => ''],
        ]);

        Carbon::setTestNow(
            Carbon::createFromFormat('Y-m-d H:i:s', '2017-10-10 10:10:10')
        );

        $post->tags()->touch();

        foreach ($post->tags()->pluck('updated_at') as $date) {
            $this->assertEquals('2017-10-10 10:10:10', $date->toDateTimeString());
        }

        $this->assertNotEquals('2017-10-10 10:10:10', Tag::find(300)->updated_at);
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
            ->withPivot('flag')
            ->wherePivot('flag', '<>', 'exclude');
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
