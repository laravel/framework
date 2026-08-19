<?php

namespace Illuminate\Tests\Integration\Database\EloquentCollectionLoadMissingTest;

use DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\LoadMissingComment;
use Illuminate\Tests\App\Models\Relationships\LoadMissingPost;
use Illuminate\Tests\App\Models\Relationships\LoadMissingUser;
use Illuminate\Tests\App\Models\Relationships\PostRelation;
use Illuminate\Tests\App\Models\Relationships\PostSubRelation;
use Illuminate\Tests\App\Models\Relationships\PostSubSubRelation;
use Illuminate\Tests\App\Models\Relationships\Revision;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentCollectionLoadMissingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedInteger('post_id');
        });

        Schema::create('revisions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('comment_id');
        });

        Schema::create('post_relations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
        });

        Schema::create('post_sub_relations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_relation_id');
        });

        Schema::create('post_sub_sub_relations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_sub_relation_id');
        });

        LoadMissingUser::create();

        LoadMissingPost::insert([
            ['user_id' => 1],
            ['user_id' => 1],
        ]);

        LoadMissingComment::insert([
            ['parent_id' => null, 'post_id' => 1],
            ['parent_id' => 1, 'post_id' => 1],
            ['parent_id' => 2, 'post_id' => 1],
        ]);

        Revision::create(['comment_id' => 1]);

        PostRelation::create(['post_id' => 2]);
        PostSubRelation::create(['post_relation_id' => 1]);
        PostSubSubRelation::create(['post_sub_relation_id' => 1]);
    }

    public function testLoadMissing()
    {
        $posts = LoadMissingPost::with('comments', 'user')->get();

        DB::enableQueryLog();

        $posts->loadMissing('comments.parent.revisions:revisions.comment_id', 'user:id');

        $this->assertCount(2, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
        $this->assertTrue($posts[0]->comments[1]->parent->relationLoaded('revisions'));
        $this->assertArrayNotHasKey('id', $posts[0]->comments[1]->parent->revisions[0]->getAttributes());
    }

    public function testLoadMissingWithClosure()
    {
        $posts = LoadMissingPost::with('comments')->get();

        DB::enableQueryLog();

        $posts->loadMissing(['comments.parent' => function ($query) {
            $query->select('id');
        }]);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
        $this->assertArrayNotHasKey('post_id', $posts[0]->comments[1]->parent->getAttributes());
    }

    public function testLoadMissingWithDuplicateRelationName()
    {
        $posts = LoadMissingPost::with('comments')->get();

        DB::enableQueryLog();

        $posts->loadMissing('comments.parent.parent');

        $this->assertCount(2, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
        $this->assertTrue($posts[0]->comments[1]->parent->relationLoaded('parent'));
    }

    public function testLoadMissingWithoutInitialLoad()
    {
        $user = LoadMissingUser::first();
        $user->loadMissing('posts.postRelation.postSubRelations.postSubSubRelations');

        $this->assertEquals(2, $user->posts->count());
        $this->assertNull($user->posts[0]->postRelation);
        $this->assertInstanceOf(PostRelation::class, $user->posts[1]->postRelation);
        $this->assertEquals(1, $user->posts[1]->postRelation->postSubRelations->count());
        $this->assertInstanceOf(PostSubRelation::class, $user->posts[1]->postRelation->postSubRelations[0]);
        $this->assertEquals(1, $user->posts[1]->postRelation->postSubRelations[0]->postSubSubRelations->count());
        $this->assertInstanceOf(PostSubSubRelation::class, $user->posts[1]->postRelation->postSubRelations[0]->postSubSubRelations[0]);
    }

    public function testLoadMissingWithNestedArraySyntax()
    {
        $posts = LoadMissingPost::with('user')->get();

        DB::enableQueryLog();

        $posts->loadMissing([
            'comments' => ['parent'],
            'user',
        ]);

        $this->assertCount(2, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
        $this->assertTrue($posts[0]->relationLoaded('user'));
    }

    public function testLoadMissingWithMultipleDotNotationRelations()
    {
        $posts = LoadMissingPost::with('comments')->get();

        DB::enableQueryLog();

        $posts->loadMissing([
            'comments.parent',
            'user.posts',
        ]);

        $this->assertCount(3, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
        $this->assertTrue($posts[0]->relationLoaded('user'));
        $this->assertTrue($posts[0]->user->relationLoaded('posts'));
    }

    public function testLoadMissingWithNestedArrayWithColon()
    {
        $posts = LoadMissingPost::with('comments')->get();

        DB::enableQueryLog();

        $posts->loadMissing(['comments' => ['parent:id']]);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
        $this->assertArrayNotHasKey('post_id', $posts[0]->comments[1]->parent->getAttributes());
    }

    public function testLoadMissingWithNestedArray()
    {
        $posts = LoadMissingPost::with('comments')->get();

        DB::enableQueryLog();

        $posts->loadMissing(['comments' => ['parent']]);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
    }

    public function testLoadMissingWithNestedArrayWithClosure()
    {
        $posts = LoadMissingPost::with('comments')->get();

        DB::enableQueryLog();

        $posts->loadMissing(['comments' => ['parent' => function ($query) {
            $query->select('id');
        }]]);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
        $this->assertArrayNotHasKey('post_id', $posts[0]->comments[1]->parent->getAttributes());
    }

    public function testLoadMissingWithMultipleNestedArrays()
    {
        $users = LoadMissingUser::get();
        $users->loadMissing([
            'posts' => [
                'postRelation' => [
                    'postSubRelations' => [
                        'postSubSubRelations',
                    ],
                ],
            ],
        ]);

        $user = $users->first();
        $this->assertEquals(2, $user->posts->count());
        $this->assertNull($user->posts[0]->postRelation);
        $this->assertInstanceOf(PostRelation::class, $user->posts[1]->postRelation);
        $this->assertEquals(1, $user->posts[1]->postRelation->postSubRelations->count());
        $this->assertInstanceOf(PostSubRelation::class, $user->posts[1]->postRelation->postSubRelations[0]);
        $this->assertEquals(1, $user->posts[1]->postRelation->postSubRelations[0]->postSubSubRelations->count());
        $this->assertInstanceOf(PostSubSubRelation::class, $user->posts[1]->postRelation->postSubRelations[0]->postSubSubRelations[0]);
    }

    public function testLoadMissingWithMultipleNestedArraysCombinedWithDotNotation()
    {
        $users = LoadMissingUser::get();
        $users->loadMissing([
            'posts' => [
                'postRelation' => [
                    'postSubRelations.postSubSubRelations',
                ],
            ],
        ]);

        $user = $users->first();
        $this->assertEquals(2, $user->posts->count());
        $this->assertNull($user->posts[0]->postRelation);
        $this->assertInstanceOf(PostRelation::class, $user->posts[1]->postRelation);
        $this->assertEquals(1, $user->posts[1]->postRelation->postSubRelations->count());
        $this->assertInstanceOf(PostSubRelation::class, $user->posts[1]->postRelation->postSubRelations[0]);
        $this->assertEquals(1, $user->posts[1]->postRelation->postSubRelations[0]->postSubSubRelations->count());
        $this->assertInstanceOf(PostSubSubRelation::class, $user->posts[1]->postRelation->postSubRelations[0]->postSubSubRelations[0]);
    }
}
