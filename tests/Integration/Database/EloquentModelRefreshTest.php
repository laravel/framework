<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelRefreshTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentModelRefreshTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function testItRefreshesModelExcludedByGlobalScope()
    {
        $post = Post::create(['title' => 'mohamed']);

        $post->refresh();
    }

    public function testItRefreshesASoftDeletedModel()
    {
        $post = Post::create(['title' => 'said']);

        Post::find($post->id)->delete();

        $this->assertFalse($post->trashed());

        $post->refresh();

        $this->assertTrue($post->trashed());
    }

    public function testItSyncsOriginalOnRefresh()
    {
        $post = Post::create(['title' => 'pat']);

        Post::find($post->id)->update(['title' => 'patrick']);

        $post->refresh();

        $this->assertEmpty($post->getDirty());

        $this->assertSame('patrick', $post->getOriginal('title'));
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = ['id'];

    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('age', function ($query) {
            $query->where('title', '!=', 'mohamed');
        });
    }
}
