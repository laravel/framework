<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Tests\Database\stubs\Post;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentWithCountJoinTest extends TestCase
{

    /**
     * Bootstrap Eloquent.
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
        $this->schema('default')->create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->timestamps();
        });

        $this->schema('default')->create('posts', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('content');
            $table->boolean('global')->default(false);
        });

        $this->schema('default')->create('locations', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('name');
        });

        $this->schema('default')->create('post_locations', function ($table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->integer('location_id');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (['default'] as $connection) {
            $this->schema($connection)->drop('users');
            $this->schema($connection)->drop('posts');
            $this->schema($connection)->drop('locations');
            $this->schema($connection)->drop('post_locations');
        }

        Relation::morphMap([], false);
    }

    public function testRelationshipJoinCount()
    {
        $user = new class extends Model {
            protected $guarded = [];
            public $table = 'users';
            function scoped_posts() {
                return $this->hasMany(Post::class, 'user_id', 'id')
                    ->where('global', '=', false)
                    ->join('post_locations', 'post_locations.post_id', '=', 'posts.id');
            }
        };

        $user = $user->newQuery()->create([ 'email' => 'troy@whitespark.ca' ]);

        $location1 = DB::table('locations')->insertGetId([ 'name' => 'Edmonton, AB', 'user_id' => $user->id ]);
        $location2 = DB::table('locations')->insertGetId([ 'name' => 'Calgary, AB', 'user_id' => $user->id ]);
        $post1 = DB::table('posts')->insertGetId([ 'content' => 'post1', 'global' => false, 'user_id' => $user->id ]);
        $post2 = DB::table('posts')->insertGetId([ 'content' => 'post2', 'global' => false, 'user_id' => $user->id ]);
        DB::table('post_locations')->insert([
            [
                'post_id' => $post1,
                'location_id' => $location1
            ],
            [
                'post_id' => $post1,
                'location_id' => $location2
            ],
            [
                'post_id' => $post2,
                'location_id' => $location1
            ],
        ]);

        $user->loadCount('scoped_posts');

        $this->assertEquals($user->scoped_posts->count(), $user->scoped_posts_count);
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
