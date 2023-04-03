<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->string('content');
        });

        Schema::create('posts_tags', function (Blueprint $table) {
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
            ['name' => 'Lortay Wellot', 'email' => 'lortay@laravel.com', 'address' => '4th Street'],
        ]);

        DB::table('posts')->insert([
            ['user_id' => 1, 'title' => 'The Post', 'content' => 'Welcome to Laravel!'],
            ['user_id' => 1, 'title' => 'The Post', 'content' => 'Welcome to the Laravel Ecosystem!'],
            ['user_id' => 1, 'title' => null, 'content' => 'Lorem Ipsum'],
            ['user_id' => 1, 'title' => 'A title', 'content' => 'Roses are red'],
            ['user_id' => 2, 'title' => 'Another title', 'content' => 'Violets are blue'],
            ['user_id' => 2, 'title' => 'Another title', 'content' => 'Taylor is turquoise'],
        ]);

        DB::table('tags')->insert([
            ['tag' => 'A tag'],
            ['tag' => 'Foo'],
            ['tag' => 'Bar'],
        ]);

        DB::table('posts_tags')->insert([
            ['post_id' => 1, 'tag_id' => 1],
            ['post_id' => 1, 'tag_id' => 2],
            ['post_id' => 2, 'tag_id' => 1],
        ]);

        DB::table('comments')->insert([
            ['post_id' => 2, 'content' => 'This is a comment']
        ]);
    }

    /**
     * @dataProvider keyByDataProvider
     */
    public function testKeyBy($keyBy, $columns, $key, $expected)
    {
        $this->assertEquals($expected, json_encode(UserKeyByTest::query()->keyBy($keyBy)->get($columns)[$key]));
    }

    public static function keyByDataProvider()
    {
        return [
            'Key by name with all columns' => ['name', ['*'], 'Lortay Wellot', '{"id":2,"name":"Lortay Wellot","email":"lortay@laravel.com","address":"4th Street"}'],
            'Key by name with selected columns not including key' => ['name', ['email', 'address'], 'Taylor Otwell', '{"email":"taylor@laravel.com","address":"5th Avenue"}'],
            'Key by name with selected columns including key' => ['name', ['name', 'email', 'address'], 'Taylor Otwell', '{"name":"Taylor Otwell","email":"taylor@laravel.com","address":"5th Avenue"}'],
            'Key by street with selected dot-columns not including key' => ['address', ['users.email'], '5th Avenue', '{"email":"taylor@laravel.com"}'],
            'Key by street with selected dot-columns including key' => ['address', ['users.address', 'email'], '5th Avenue', '{"address":"5th Avenue","email":"taylor@laravel.com"}'],
        ];
    }

    /**
     * @dataProvider keyByWithSelectDataProvider
     */
    public function testKeyByWithSelect($keyBy, $columns, $key, $expected)
    {
        $results = UserKeyByTest::query()->keyBy($keyBy)->select($columns)->get();

        $this->assertSame($expected, $results[$key]->getAttributes());
    }

    public static function keyByWithSelectDataProvider()
    {
        return [
            'keyBy column does not become part of the result if not SELECTed' => ['name', ['address'], 'Taylor Otwell', ['address' => '5th Avenue']],
            'keyBy column becomes part of the result if SELECTed' => ['name', ['name', 'address'], 'Taylor Otwell', ['name' => 'Taylor Otwell', 'address' => '5th Avenue']],
        ];
    }

    public function testHasManyRelation()
    {
        $this->assertInstanceOf(
            PostKeyByTest::class,
            UserKeyByTest::query()->with('posts')->first()->posts['The Post']
        );
    }

    public function testHasManyThroughRelation()
    {
        $this->assertInstanceOf(
            CommentKeyByTest::class,
            UserKeyByTest::query()->with('posts', 'comments')->first()->comments['This is a comment']
        );
    }

    public function testBelongsToMany()
    {
        $this->assertInstanceOf(
            TagKeyByTest::class,
            UserKeyByTest::query()->with('tags')->first()->tags['Foo']
        );
    }
}

class UserKeyByTest extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;

    public function posts()
    {
        return $this->hasMany(PostKeyByTest::class, 'user_id')->keyBy('title');
    }

    public function comments()
    {
        return $this->hasManyThrough(CommentKeyByTest::class, PostKeyByTest::class, 'user_id', 'post_id')->keyBy('content');
    }

    public function tags()
    {
        return $this->belongsToMany(TagKeyByTest::class, 'posts_tags', 'tag_id', 'post_id')->keyBy('tag');
    }
}

class PostKeyByTest extends Model
{
    protected $table = 'posts';
    protected $guarded = [];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(UserKeyByTest::class);
    }
}

class CommentKeyByTest extends Model
{
    protected $table = 'comments';
    protected $guarded = [];
    public $timestamps = true;
}

class TagKeyByTest extends Model
{
    protected $table = 'tags';
    protected $guarded = [];
    public $timestamps = true;
}
