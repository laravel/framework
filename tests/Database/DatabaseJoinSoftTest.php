<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class DatabaseJoinSoftTest extends TestCase
{
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

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->foreignId('user_id')->constrained('users');
            $table->string('title');
            $table->softDeletes();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('posts');
    }

    /**
     * Add some users and posts.
     */
    protected function seedData()
    {
        DB::table('users')->insert([
            ['name' => 'Alice'],
            ['name' => 'Bob'],
        ]);

        DB::table('posts')->insert([
            ['user_id' => 1, 'title' => 'Post 1', 'deleted_at' => null],
            ['user_id' => 1, 'title' => 'Post 2', 'deleted_at' => now()],
            ['user_id' => 2, 'title' => 'Post 3', 'deleted_at' => null],
            ['user_id' => 2, 'title' => 'Post 4', 'deleted_at' => now()],
        ]);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    public function testEloquentJoinSoftExcludesSoftDeletedRecords()
    {
        $this->seedData();

        // Use joinSoft to join users with posts, exclude soft-deleted posts
        $items = User::joinSoft('posts', 'users.id', 'posts.user_id')->get();

        // Ensure that only the posts from the non-deleted user are returned
        $this->assertCount(2, $items);
        $this->assertEquals('Alice', $items[0]->name);
        $this->assertEquals('Post 1', $items[0]->title);
        $this->assertEquals('Post 3', $items[1]->title);
    }

    public function testDBJoinSoftExcludesSoftDeletedRecords()
    {
        $this->seedData();

        // Use joinSoft to join users with posts, exclude soft-deleted posts
        $query = DB::table('users')
            ->joinSoft('posts', 'users.id', '=', 'posts.user_id')
            ->select('users.name', 'posts.title')
            ->get();

        // Ensure that only the posts from the non-deleted user are returned
        $this->assertCount(2, $query);
        $this->assertEquals('Alice', $query[0]->name);
        $this->assertEquals('Post 1', $query[0]->title);
        $this->assertEquals('Post 3', $query[1]->title);
    }
}

class User extends Eloquent
{
}
