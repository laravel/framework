<?php

namespace Illuminate\Tests\Integration\Database\EloquentKeyByTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentKeyByTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('address');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('title')->nullable();
            $table->string('content');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->string('content');
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('tag_id');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tag');
        });

        DB::table('users')->insert([
            ['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com', 'address' => '5th Avenue'],
            ['name' => 'Taylor Otwell', 'email' => 'other_taylor@laravel.com', 'address' => '7th Avenue'],
            ['name' => 'Lortay Wellot', 'email' => 'lortay@laravel.com', 'address' => '4th Street'],
        ]);

        DB::table('posts')->insert([
            ['user_id' => 1, 'title' => 'The Post', 'content' => 'Welcome to Laravel!', 'created_at' => '2023-01-04 12:00:00'],
            ['user_id' => 1, 'title' => 'The Post', 'content' => 'Welcome to the Laravel Ecosystem!', 'created_at' => '2023-03-04 13:00:00'],
            ['user_id' => 1, 'title' => null, 'content' => 'Lorem Ipsum', 'created_at' => '2023-05-04 14:00:00'],
            ['user_id' => 1, 'title' => 'A title', 'content' => 'Roses are red', 'created_at' => '2023-07-04 15:00:00'],
            ['user_id' => 2, 'title' => 'Another title', 'content' => 'Violets are blue', 'created_at' => '2023-09-04 16:00:00'],
            ['user_id' => 2, 'title' => 'Another title', 'content' => 'Taylor is turquoise', 'created_at' => '2023-11-04 17:00:00'],
        ]);

        DB::table('tags')->insert([
            ['tag' => 'A tag'],
            ['tag' => 'Foo'],
            ['tag' => 'Bar'],
        ]);

        DB::table('post_tag')->insert([
            ['post_id' => 1, 'tag_id' => 1],
            ['post_id' => 1, 'tag_id' => 2],
            ['post_id' => 2, 'tag_id' => 1],
        ]);

        DB::table('comments')->insert([
            ['post_id' => 2, 'content' => 'This is a comment'],
        ]);
    }

    /**
     * @dataProvider keyByDataProvider
     */
    public function testKeyBy($keyBy, $columns, $key, $expected)
    {
        $this->assertEquals($expected, json_encode(User::query()->keyBy($keyBy)->get($columns)[$key]));
    }

    public static function keyByDataProvider()
    {
        return [
            'Key by name with all columns' => ['name', ['*'], 'Lortay Wellot', '{"id":3,"name":"Lortay Wellot","email":"lortay@laravel.com","address":"4th Street"}'],
            'Key by name with selected columns not including key' => ['name', ['email', 'address'], 'Taylor Otwell', '{"email":"other_taylor@laravel.com","address":"7th Avenue"}'],
            'Key by name with selected columns including key' => ['name', ['name', 'email', 'address'], 'Taylor Otwell', '{"name":"Taylor Otwell","email":"other_taylor@laravel.com","address":"7th Avenue"}'],
            'Key by street with selected dot-columns not including key' => ['address', ['users.email'], '5th Avenue', '{"email":"taylor@laravel.com"}'],
            'Key by street with selected dot-columns including key' => ['address', ['users.address', 'email'], '5th Avenue', '{"address":"5th Avenue","email":"taylor@laravel.com"}'],
        ];
    }

    /**
     * @dataProvider keyByWithSelectDataProvider
     */
    public function testKeyByWithSelect($keyBy, $columns, $key, $expected)
    {
        $results = User::query()->keyBy($keyBy)->select($columns)->get();

        $this->assertSame($expected, $results[$key]->getAttributes());
    }

    public static function keyByWithSelectDataProvider()
    {
        return [
            'keyBy column does not become part of the result if not SELECTed' => ['name', ['address'], 'Taylor Otwell', ['address' => '7th Avenue']],
            'keyBy column becomes part of the result if SELECTed' => ['name', ['name', 'address'], 'Taylor Otwell', ['name' => 'Taylor Otwell', 'address' => '7th Avenue']],
        ];
    }

    public function testGroupBySimilarity()
    {
        $this->assertSame(
            Post::query()->groupBy('title')->select(['title', DB::raw('MIN(id) as id')])
                ->keyBy('title')->get()->toArray(),
            Post::query()->groupBy('title')->select(['title', DB::raw('MIN(id) as id')])
                ->get()->keyBy('title')->toArray()
        );
    }

    public function testCollectionKeyBySimilarity()
    {
        $this->assertSame(
            User::query()->keyBy('name')->get()->toArray(),
            User::query()->get()->keyBy('name')->toArray()
        );
    }

    public function testHasManyRelation()
    {
        $this->assertInstanceOf(
            Post::class,
            User::query()->with('posts')->first()->posts['The Post']
        );
    }

    public function testHasManyThroughRelation()
    {
        $user = User::query()->with('posts', 'comments')->first();
        $this->assertInstanceOf(Comment::class, $user->comments['This is a comment']);
        $this->assertInstanceOf(Post::class, $user->posts['The Post']);
    }

    public function testBelongsToMany()
    {
        $this->assertInstanceOf(
            Tag::class,
            Post::query()->with('tags')->first()->tags['Foo']
        );
    }

    public function testKeyByCustomColumn()
    {
        $results = User::query()->with('postsByDate')->first();
        $this->assertSame(
            ['2023-01-04', '2023-03-04', '2023-05-04', '2023-07-04'],
            $results->postsByDate->keys()->toArray()
        );
    }
}

class BaseModel extends Model
{
    public $timestamps = false;
}

class User extends BaseModel
{
    public function comments()
    {
        return $this->hasManyThrough(Comment::class, Post::class)->keyBy('content');
    }

    public function posts()
    {
        return $this->hasMany(Post::class)->keyBy('title');
    }

    public function postsByDate()
    {
        $cast = match (DB::connection()->getDriverName()) {
            'mysql', 'sqlite' => 'SUBSTR(created_at, 1, 10)',
            'pgsql' => 'SUBSTR(CAST(created_at AS varchar), 1, 10)',
            'sqlsrv' => 'SUBSTRING(CAST(created_at AS varchar), 1, 10)'
        };
        return $this->hasMany(Post::class)->keyBy(DB::raw($cast));
    }
}

class Post extends BaseModel
{
    public $timestamps = true;

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->keyBy('tag');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class Comment extends BaseModel
{
    //
}

class Tag extends BaseModel
{
    //
}
