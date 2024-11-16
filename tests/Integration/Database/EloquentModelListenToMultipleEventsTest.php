<?php

namespace Illuminate\Tests\Integration\Database;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\Fixtures\Post;

class EloquentModelListenToMultipleEventsTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Post::unguard();
    }

    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });
    }

    public function testEloquentModelCanListenToMultipleEvents()
    {
        Event::fake();

        Post::listen(['saved', 'deleted'], function () {
            // do something
        });

        $post = Post::query()->create(['title' => 'Third post']);
        $post->delete();

        Event::assertListening('eloquent.saved: '.Post::class, Closure::class);
        Event::assertListening('eloquent.deleted: '.Post::class, Closure::class);
    }

    public function testEloquentModelCanListenToWildcardEvents()
    {
        Event::fake();

        Post::listen(function () {
            // do something
        });

        Post::query()->create(['title' => 'First post']);

        Event::assertListening('eloquent.booting: '.Post::class, Closure::class);
        Event::assertListening('eloquent.booted: '.Post::class, Closure::class);
        Event::assertListening('eloquent.saving: '.Post::class, Closure::class);
        Event::assertListening('eloquent.creating: '.Post::class, Closure::class);
        Event::assertListening('eloquent.created: '.Post::class, Closure::class);
        Event::assertListening('eloquent.saved: '.Post::class, Closure::class);
    }
}
