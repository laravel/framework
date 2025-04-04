<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBuilderMemoryOptimizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
        $this->schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });

        $this->schema()->create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->string('body');
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
        parent::tearDown();

        $this->schema()->drop('users');
        $this->schema()->drop('posts');
        $this->schema()->drop('comments');
    }

    /**
     * Get the schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return DB::connection()->getSchemaBuilder();
    }

    public function testChunkByMemoryAutomaticallyAdjustsChunkSize()
    {
        // Create test data
        for ($i = 1; $i <= 100; $i++) {
            TestUserModel::create([
                'email' => "user{$i}@example.com",
                'name' => "User {$i}",
                'address' => "Address {$i}",
                'phone' => "555-000-{$i}",
            ]);
        }

        $iterations = 0;
        $totalProcessed = 0;

        $initialMemory = memory_get_usage();

        TestUserModel::query()->chunkByMemory(10, function ($users, $page) use (&$iterations, &$totalProcessed) {
            $iterations++;
            $totalProcessed += count($users);
            // Simulate some processing
            $users->map(function ($user) {
                return $user->name.' - '.$user->email;
            })->implode(', ');
        });

        $this->assertEquals(100, $totalProcessed);
        $this->assertLessThan(10, $iterations); // Should be optimized to fewer than 10 iterations

        // Verify memory usage is controlled
        $finalMemory = memory_get_usage();
        $this->assertLessThan($initialMemory * 2, $finalMemory);
    }

    public function testLazyByMemoryCreatesLazyCollection()
    {
        // Create test data
        for ($i = 1; $i <= 100; $i++) {
            TestUserModel::create([
                'email' => "user{$i}@example.com",
                'name' => "User {$i}",
            ]);
        }

        $collection = TestUserModel::query()->lazyByMemory(10);

        $this->assertInstanceOf(LazyCollection::class, $collection);
        $this->assertEquals(100, $collection->count());
    }

    public function testModelMemoryOptimization()
    {
        $user = TestUserModel::create([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'address' => '123 Test St',
            'phone' => '555-1234',
        ]);

        // Create related posts
        for ($i = 1; $i <= 5; $i++) {
            $post = $user->posts()->create([
                'title' => "Post {$i}",
                'content' => "Content {$i}",
            ]);

            // Create comments for each post
            for ($j = 1; $j <= 3; $j++) {
                $post->comments()->create([
                    'body' => "Comment {$j} on Post {$i}",
                ]);
            }
        }

        // Load the user with all relations
        $userWithRelations = TestUserModel::with('posts.comments')->find($user->id);

        // Verify all relations are loaded
        $this->assertTrue($userWithRelations->relationLoaded('posts'));
        $this->assertTrue($userWithRelations->posts->first()->relationLoaded('comments'));

        // Get list of attributes before optimization
        $attributesBeforeOptimize = array_keys($userWithRelations->getAttributes());
        $this->assertContains('address', $attributesBeforeOptimize);
        $this->assertContains('phone', $attributesBeforeOptimize);

        // Optimize the user model memory (keep only specific attributes and relations)
        $userWithRelations->optimizeMemory(['posts'], ['id', 'email', 'name']);

        // The posts relation should still be loaded
        $this->assertTrue($userWithRelations->relationLoaded('posts'));

        // Check that only the specified attributes are kept
        $attributesAfterOptimize = array_keys($userWithRelations->getAttributes());
        $this->assertContains('id', $attributesAfterOptimize);
        $this->assertContains('email', $attributesAfterOptimize);
        $this->assertContains('name', $attributesAfterOptimize);
        $this->assertNotContains('address', $attributesAfterOptimize);
        $this->assertNotContains('phone', $attributesAfterOptimize);
    }

    public function testWithLimitedRelations()
    {
        // Create test data
        $user = TestUserModel::create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        // Create a large number of posts
        for ($i = 1; $i <= 20; $i++) {
            $user->posts()->create([
                'title' => "Post {$i}",
                'content' => "Content {$i}",
            ]);
        }

        // Test withLimited to load only a subset of posts
        $userWithLimitedPosts = TestUserModel::withLimited('posts', ['posts' => 5])->find($user->id);

        $this->assertCount(5, $userWithLimitedPosts->posts);
    }
}

class TestUserModel extends Model
{
    protected $table = 'users';
    protected $fillable = ['email', 'name', 'address', 'phone'];

    public function posts()
    {
        return $this->hasMany(TestPostModel::class, 'user_id');
    }
}

class TestPostModel extends Model
{
    protected $table = 'posts';
    protected $fillable = ['user_id', 'title', 'content'];

    public function user()
    {
        return $this->belongsTo(TestUserModel::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(TestCommentModel::class, 'post_id');
    }
}

class TestCommentModel extends Model
{
    protected $table = 'comments';
    protected $fillable = ['post_id', 'body'];

    public function post()
    {
        return $this->belongsTo(TestPostModel::class, 'post_id');
    }
}
