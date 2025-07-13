<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\Fixtures\Post;
use Illuminate\Tests\Integration\Database\Fixtures\PostStringyKey;

class EloquentPatchTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }

    public function testPatch()
    {
        Schema::create('my_posts', function (Blueprint $table) {
            $table->increments('my_id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Model::unguard();

        Post::query()->create(['title' => 'title1']);
        Post::query()->create(['title' => 'title2']);

        Post::updated(fn ($model) => $_SERVER['update']['updated'][] = $model->id);
        Post::updating(fn ($model) => $_SERVER['update']['updating'][] = $model->id);

        Post::patch([1, 2], ['title' => 'title3']);

        $this->assertEquals([1, 2], $_SERVER['update']['updating']);
        $this->assertEquals([1, 2], $_SERVER['update']['updated']);
        $this->assertEquals(Post::query()->pluck('title')->all(), ['title3', 'title3']);

        PostStringyKey::query()->create(['title' => 'title1']);
        PostStringyKey::query()->create(['title' => 'title2']);

        unset($_SERVER['update']);
        PostStringyKey::updated(fn ($model) => $_SERVER['update']['updated'][] = $model->my_id);
        PostStringyKey::updating(fn ($model) => $_SERVER['update']['updating'][] = $model->my_id);

        PostStringyKey::patch([1, 2], ['title' => 'title3']);

        $this->assertEquals([1, 2], $_SERVER['update']['updating']);
        $this->assertEquals([1, 2], $_SERVER['update']['updated']);
        $this->assertEquals(PostStringyKey::query()->pluck('title')->all(), ['title3', 'title3']);
    }
}
