<?php

namespace Illuminate\Tests\Integration\Database\EloquentRelationRestoreOrCreateTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentRelationRestoreOrCreateTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('country_id')->nullable();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('title');
            $table->softDeletes();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('body')->unique();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('role_id');
        });
    }

    public function testRestoreOrCreateOnHasMany()
    {
        $user = User::create();
        $other = User::create();

        $post = $user->posts()->create(['title' => 'Draft']);
        $post->delete();
        $other->posts()->create(['title' => 'Draft'])->delete();

        $restored = $user->posts()->restoreOrCreate(['title' => 'Draft']);

        $this->assertTrue($restored->is($post));
        $this->assertFalse($restored->trashed());

        $created = $user->posts()->restoreOrCreate(['title' => 'Published']);

        $this->assertEquals($user->id, $created->user_id);
        $this->assertEquals($user->id, Post::where('title', 'Published')->value('user_id'));
    }

    public function testCreateOrRestoreOnHasMany()
    {
        $user = User::create();

        $created = $user->posts()->createOrRestore(['title' => 'Draft']);

        $this->assertEquals($user->id, $created->user_id);
        $this->assertEquals($user->id, Post::where('title', 'Draft')->value('user_id'));
    }

    public function testRestoreOrCreateOnHasManyThrough()
    {
        $country = Country::create();
        $otherUser = User::create(['country_id' => Country::create()->id]);
        $user = User::create(['country_id' => $country->id]);

        $otherUser->posts()->create(['title' => 'Other'])->delete();
        $otherUser->posts()->create(['title' => 'Other'])->delete();
        $post = $user->posts()->create(['title' => 'Draft']);
        $post->delete();

        $restored = $country->posts()->restoreOrCreate(['title' => 'Draft']);

        $this->assertTrue($restored->is($post));
        $this->assertFalse($post->fresh()->trashed());
        $this->assertSame(2, Post::onlyTrashed()->where('title', 'Other')->count());
    }

    public function testCreateOrRestoreOnHasManyThrough()
    {
        $country = Country::create();
        $otherUser = User::create(['country_id' => Country::create()->id]);
        $user = User::create(['country_id' => $country->id]);

        $otherUser->comments()->create(['body' => 'First'])->delete();
        $otherUser->comments()->create(['body' => 'Second'])->delete();
        $comment = $user->comments()->create(['body' => 'Draft']);
        $comment->delete();

        $restored = $country->comments()->createOrRestore(['body' => 'Draft']);

        $this->assertTrue($restored->is($comment));
        $this->assertFalse($comment->fresh()->trashed());
        $this->assertSame(2, Comment::onlyTrashed()->count());
    }

    public function testRestoreOrCreateOnBelongsToMany()
    {
        $user = User::create();

        $other = Role::create(['name' => 'other']);
        $other->delete();

        $editor = Role::create(['name' => 'editor']);
        $user->roles()->attach($editor);
        $editor->delete();

        $restored = $user->roles()->restoreOrCreate(['name' => 'editor']);

        $this->assertTrue($restored->is($editor));
        $this->assertFalse($editor->fresh()->trashed());
        $this->assertTrue($other->fresh()->trashed());

        $created = $user->roles()->restoreOrCreate(['name' => 'admin']);

        $this->assertTrue($user->roles()->whereKey($created->getKey())->exists());
    }

    public function testRestoreOrCreateOnBelongsToManyRestoresUnattachedModel()
    {
        $user = User::create();

        $role = Role::create(['name' => 'admin']);
        $role->delete();

        $restored = $user->roles()->restoreOrCreate(['name' => 'admin']);

        $this->assertTrue($restored->is($role));
        $this->assertFalse($role->fresh()->trashed());
        $this->assertSame(1, Role::withTrashed()->count());
        $this->assertTrue($user->roles()->whereKey($role->getKey())->exists());
    }

    public function testCreateOrRestoreOnBelongsToMany()
    {
        $user = User::create();

        $created = $user->roles()->createOrRestore(['name' => 'admin']);

        $this->assertTrue($user->roles()->whereKey($created->getKey())->exists());
    }
}

class Country extends Model
{
    public $timestamps = false;

    public function posts()
    {
        return $this->hasManyThrough(Post::class, User::class);
    }

    public function comments()
    {
        return $this->hasManyThrough(Comment::class, User::class);
    }
}

class User extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}

class Post extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];
}

class Comment extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];
}

class Role extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];
}
