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
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('title');
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

    public function testCreateOrRestoreOnBelongsToMany()
    {
        $user = User::create();

        $created = $user->roles()->createOrRestore(['name' => 'admin']);

        $this->assertTrue($user->roles()->whereKey($created->getKey())->exists());
    }
}

class User extends Model
{
    public $timestamps = false;

    public function posts()
    {
        return $this->hasMany(Post::class);
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

class Role extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];
}
