<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToTest;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentBelongsToTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->nullable();
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('parent_slug')->nullable();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->string('title');
            $table->timestamps();
        });

        $user = User::create(['slug' => Str::random()]);
        User::create(['parent_id' => $user->id, 'parent_slug' => $user->slug]);

        $post = Post::create(['title' => 'dives']);
        $user->posts()->save($post);
    }

    public function test_has_self()
    {
        $users = User::has('parent')->get();

        $this->assertEquals(1, $users->count());
    }

    public function test_has_self_custom_owner_key()
    {
        $users = User::has('parentBySlug')->get();

        $this->assertEquals(1, $users->count());
    }

    public function test_associate_with_model()
    {
        $parent = User::doesntHave('parent')->first();
        $child = User::has('parent')->first();

        $parent->parent()->associate($child);

        $this->assertEquals($child->id, $parent->parent_id);
        $this->assertEquals($child->id, $parent->parent->id);
    }

    public function test_associate_with_id()
    {
        $parent = User::doesntHave('parent')->first();
        $child = User::has('parent')->first();

        $parent->parent()->associate($child->id);

        $this->assertEquals($child->id, $parent->parent_id);
        $this->assertEquals($child->id, $parent->parent->id);
    }

    public function test_associate_with_id_unsets_loaded_relation()
    {
        $child = User::has('parent')->with('parent')->first();

        // Overwrite the (loaded) parent relation
        $child->parent()->associate($child->id);

        $this->assertEquals($child->id, $child->parent_id);
        $this->assertFalse($child->relationLoaded('parent'));
    }

    public function test_updateOrCreate_method()
    {
        $user = User::first();

        $user->posts()->updateOrCreate(['title' => 'dives'], ['title' => 'wavez']);
        $this->assertEquals('wavez', $user->posts()->first()->fresh()->title);

        $user->posts()->updateOrCreate(['id' => 'asd'], ['title' => 'vapor']);
        $this->assertNotNull($user->posts()->whereTitle('vapor')->first());

        $user = User::with('posts')->first();

        DB::enableQueryLog();
        $user->posts()->updateOrCreate([], ['title' => 'nova']);
        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals('nova', $user->fresh()->posts()->first()->title);
    }
}

class User extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function parentBySlug()
    {
        return $this->belongsTo(self::class, 'parent_slug', 'slug');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}


class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = ['id'];

    public function owner()
    {
        return $this->belongsTo(User::class);
    }
}
