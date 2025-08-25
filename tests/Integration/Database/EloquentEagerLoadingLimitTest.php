<?php

namespace Illuminate\Tests\Integration\Database\EloquentEagerLoadingLimitTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentEagerLoadingLimitTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
        });

        User::create();
        User::create();

        Post::create(['user_id' => 1, 'created_at' => new Carbon('2024-01-01 00:00:01')]);
        Post::create(['user_id' => 1, 'created_at' => new Carbon('2024-01-01 00:00:02')]);
        Post::create(['user_id' => 1, 'created_at' => new Carbon('2024-01-01 00:00:03')]);
        Post::create(['user_id' => 2, 'created_at' => new Carbon('2024-01-01 00:00:04')]);
        Post::create(['user_id' => 2, 'created_at' => new Carbon('2024-01-01 00:00:05')]);
        Post::create(['user_id' => 2, 'created_at' => new Carbon('2024-01-01 00:00:06')]);

        Comment::create(['post_id' => 1, 'created_at' => new Carbon('2024-01-01 00:00:01')]);
        Comment::create(['post_id' => 2, 'created_at' => new Carbon('2024-01-01 00:00:02')]);
        Comment::create(['post_id' => 3, 'created_at' => new Carbon('2024-01-01 00:00:03')]);
        Comment::create(['post_id' => 4, 'created_at' => new Carbon('2024-01-01 00:00:04')]);
        Comment::create(['post_id' => 5, 'created_at' => new Carbon('2024-01-01 00:00:05')]);
        Comment::create(['post_id' => 6, 'created_at' => new Carbon('2024-01-01 00:00:06')]);

        Role::create(['created_at' => new Carbon('2024-01-01 00:00:01')]);
        Role::create(['created_at' => new Carbon('2024-01-01 00:00:02')]);
        Role::create(['created_at' => new Carbon('2024-01-01 00:00:03')]);
        Role::create(['created_at' => new Carbon('2024-01-01 00:00:04')]);
        Role::create(['created_at' => new Carbon('2024-01-01 00:00:05')]);
        Role::create(['created_at' => new Carbon('2024-01-01 00:00:06')]);

        DB::table('role_user')->insert([
            ['role_id' => 1, 'user_id' => 1],
            ['role_id' => 2, 'user_id' => 1],
            ['role_id' => 3, 'user_id' => 1],
            ['role_id' => 4, 'user_id' => 2],
            ['role_id' => 5, 'user_id' => 2],
            ['role_id' => 6, 'user_id' => 2],
        ]);
    }

    public function testBelongsToMany(): void
    {
        $users = User::with(['roles' => fn ($query) => $query->latest()->limit(2)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([3, 2], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([6, 5], $users[1]->roles->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_row', $users[0]->roles[0]);
        $this->assertArrayNotHasKey('@laravel_group := `user_id`', $users[0]->roles[0]);
    }

    public function testBelongsToManyWithOffset(): void
    {
        $users = User::with(['roles' => fn ($query) => $query->latest()->limit(2)->offset(1)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([2, 1], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([5, 4], $users[1]->roles->pluck('id')->all());
    }

    public function testHasMany(): void
    {
        $users = User::with(['posts' => fn ($query) => $query->latest()->limit(2)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([3, 2], $users[0]->posts->pluck('id')->all());
        $this->assertEquals([6, 5], $users[1]->posts->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_row', $users[0]->posts[0]);
        $this->assertArrayNotHasKey('@laravel_group := `user_id`', $users[0]->posts[0]);
    }

    public function testHasManyWithOffset(): void
    {
        $users = User::with(['posts' => fn ($query) => $query->latest()->limit(2)->offset(1)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([2, 1], $users[0]->posts->pluck('id')->all());
        $this->assertEquals([5, 4], $users[1]->posts->pluck('id')->all());
    }

    public function testHasManyThrough(): void
    {
        $users = User::with(['comments' => fn ($query) => $query->latest('comments.created_at')->limit(2)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([3, 2], $users[0]->comments->pluck('id')->all());
        $this->assertEquals([6, 5], $users[1]->comments->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_row', $users[0]->comments[0]);
        $this->assertArrayNotHasKey('@laravel_group := `user_id`', $users[0]->comments[0]);
    }

    public function testHasManyThroughWithOffset(): void
    {
        $users = User::with(['comments' => fn ($query) => $query->latest('comments.created_at')->limit(2)->offset(1)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([2, 1], $users[0]->comments->pluck('id')->all());
        $this->assertEquals([5, 4], $users[1]->comments->pluck('id')->all());
    }
}

class Comment extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}

class Post extends Model
{
    protected $guarded = [];
}

class Role extends Model
{
    protected $guarded = [];
}

class User extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(Comment::class, Post::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
