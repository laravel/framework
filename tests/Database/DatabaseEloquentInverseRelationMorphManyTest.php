<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Tests\App\Models\Relationships\MorphManyInverseCommentModel;
use Illuminate\Tests\App\Models\Relationships\MorphManyInversePostModel;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentInverseRelationMorphManyTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->schema()->create('test_posts', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        $this->schema()->create('test_comments', function ($table) {
            $table->increments('id');
            $table->morphs('commentable');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('test_posts');
        $this->schema()->drop('test_comments');
    }

    public function testMorphManyInverseRelationIsProperlySetToParentWhenLazyLoaded()
    {
        MorphManyInversePostModel::factory()->withComments()->count(3)->create();
        $posts = MorphManyInversePostModel::all();

        foreach ($posts as $post) {
            $this->assertFalse($post->relationLoaded('comments'));
            $comments = $post->comments;
            foreach ($comments as $comment) {
                $this->assertTrue($comment->relationLoaded('commentable'));
                $this->assertSame($post, $comment->commentable);
            }
        }
    }

    public function testMorphManyInverseRelationIsProperlySetToParentWhenEagerLoaded()
    {
        MorphManyInversePostModel::factory()->withComments()->count(3)->create();
        $posts = MorphManyInversePostModel::with('comments')->get();

        foreach ($posts as $post) {
            $comments = $post->getRelation('comments');

            foreach ($comments as $comment) {
                $this->assertTrue($comment->relationLoaded('commentable'));
                $this->assertSame($post, $comment->commentable);
            }
        }
    }

    public function testMorphManyGuessedInverseRelationIsProperlySetToParentWhenLazyLoaded()
    {
        MorphManyInversePostModel::factory()->withComments()->count(3)->create();
        $posts = MorphManyInversePostModel::all();

        foreach ($posts as $post) {
            $this->assertFalse($post->relationLoaded('guessedComments'));
            $comments = $post->guessedComments;
            foreach ($comments as $comment) {
                $this->assertTrue($comment->relationLoaded('commentable'));
                $this->assertSame($post, $comment->commentable);
            }
        }
    }

    public function testMorphManyGuessedInverseRelationIsProperlySetToParentWhenEagerLoaded()
    {
        MorphManyInversePostModel::factory()->withComments()->count(3)->create();
        $posts = MorphManyInversePostModel::with('guessedComments')->get();

        foreach ($posts as $post) {
            $comments = $post->getRelation('guessedComments');

            foreach ($comments as $comment) {
                $this->assertTrue($comment->relationLoaded('commentable'));
                $this->assertSame($post, $comment->commentable);
            }
        }
    }

    public function testMorphLatestOfManyInverseRelationIsProperlySetToParentWhenLazyLoaded()
    {
        MorphManyInversePostModel::factory()->count(3)->withComments()->create();
        $posts = MorphManyInversePostModel::all();

        foreach ($posts as $post) {
            $this->assertFalse($post->relationLoaded('lastComment'));
            $comment = $post->lastComment;

            $this->assertTrue($comment->relationLoaded('commentable'));
            $this->assertSame($post, $comment->commentable);
        }
    }

    public function testMorphLatestOfManyInverseRelationIsProperlySetToParentWhenEagerLoaded()
    {
        MorphManyInversePostModel::factory()->count(3)->withComments()->create();
        $posts = MorphManyInversePostModel::with('lastComment')->get();

        foreach ($posts as $post) {
            $comment = $post->getRelation('lastComment');

            $this->assertTrue($comment->relationLoaded('commentable'));
            $this->assertSame($post, $comment->commentable);
        }
    }

    public function testMorphLatestOfManyGuessedInverseRelationIsProperlySetToParentWhenLazyLoaded()
    {
        MorphManyInversePostModel::factory()->count(3)->withComments()->create();
        $posts = MorphManyInversePostModel::all();

        foreach ($posts as $post) {
            $this->assertFalse($post->relationLoaded('guessedLastComment'));
            $comment = $post->guessedLastComment;

            $this->assertTrue($comment->relationLoaded('commentable'));
            $this->assertSame($post, $comment->commentable);
        }
    }

    public function testMorphLatestOfManyGuessedInverseRelationIsProperlySetToParentWhenEagerLoaded()
    {
        MorphManyInversePostModel::factory()->count(3)->withComments()->create();
        $posts = MorphManyInversePostModel::with('guessedLastComment')->get();

        foreach ($posts as $post) {
            $comment = $post->getRelation('guessedLastComment');

            $this->assertTrue($comment->relationLoaded('commentable'));
            $this->assertSame($post, $comment->commentable);
        }
    }

    public function testMorphOneOfManyInverseRelationIsProperlySetToParentWhenLazyLoaded()
    {
        MorphManyInversePostModel::factory()->count(3)->withComments()->create();
        $posts = MorphManyInversePostModel::all();

        foreach ($posts as $post) {
            $this->assertFalse($post->relationLoaded('firstComment'));
            $comment = $post->firstComment;

            $this->assertTrue($comment->relationLoaded('commentable'));
            $this->assertSame($post, $comment->commentable);
        }
    }

    public function testMorphOneOfManyInverseRelationIsProperlySetToParentWhenEagerLoaded()
    {
        MorphManyInversePostModel::factory()->count(3)->withComments()->create();
        $posts = MorphManyInversePostModel::with('firstComment')->get();

        foreach ($posts as $post) {
            $comment = $post->getRelation('firstComment');

            $this->assertTrue($comment->relationLoaded('commentable'));
            $this->assertSame($post, $comment->commentable);
        }
    }

    public function testMorphManyInverseRelationIsProperlySetToParentWhenMakingMany()
    {
        $post = MorphManyInversePostModel::create();

        $comments = $post->comments()->makeMany(array_fill(0, 3, []));

        foreach ($comments as $comment) {
            $this->assertTrue($comment->relationLoaded('commentable'));
            $this->assertSame($post, $comment->commentable);
        }
    }

    public function testMorphManyInverseRelationIsProperlySetToParentWhenCreatingMany()
    {
        $post = MorphManyInversePostModel::create();

        $comments = $post->comments()->createMany(array_fill(0, 3, []));

        foreach ($comments as $comment) {
            $this->assertTrue($comment->relationLoaded('commentable'));
            $this->assertSame($post, $comment->commentable);
        }
    }

    public function testMorphManyInverseRelationIsProperlySetToParentWhenCreatingManyQuietly()
    {
        $post = MorphManyInversePostModel::create();

        $comments = $post->comments()->createManyQuietly(array_fill(0, 3, []));

        foreach ($comments as $comment) {
            $this->assertTrue($comment->relationLoaded('commentable'));
            $this->assertSame($post, $comment->commentable);
        }
    }

    public function testMorphManyInverseRelationIsProperlySetToParentWhenSavingMany()
    {
        $post = MorphManyInversePostModel::create();
        $comments = array_fill(0, 3, new MorphManyInverseCommentModel);

        $post->comments()->saveMany($comments);

        foreach ($comments as $comment) {
            $this->assertTrue($comment->relationLoaded('commentable'));
            $this->assertSame($post, $comment->commentable);
        }
    }

    public function testMorphManyInverseRelationIsProperlySetToParentWhenUpdatingMany()
    {
        $post = MorphManyInversePostModel::create();
        $comments = MorphManyInverseCommentModel::factory()->count(3)->create();

        foreach ($comments as $comment) {
            $this->assertTrue($post->isNot($comment->commentable));
        }

        $post->comments()->saveMany($comments);

        foreach ($comments as $comment) {
            $this->assertSame($post, $comment->commentable);
        }
    }

    /**
     * Helpers...
     */

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}
