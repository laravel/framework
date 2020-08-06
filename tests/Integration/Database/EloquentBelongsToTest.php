<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

        $user = User::create(['slug' => Str::random()]);
        User::create(['parent_id' => $user->id, 'parent_slug' => $user->slug]);
    }

    public function testHasSelf()
    {
        $users = User::has('parent')->get();

        $this->assertCount(1, $users);
    }

    public function testHasSelfCustomOwnerKey()
    {
        $users = User::has('parentBySlug')->get();

        $this->assertCount(1, $users);
    }

    public function testAssociateWithModel()
    {
        $parent = User::doesntHave('parent')->first();
        $child = User::has('parent')->first();

        $parent->parent()->associate($child);

        $this->assertEquals($child->id, $parent->parent_id);
        $this->assertEquals($child->id, $parent->parent->id);
    }

    public function testAssociateWithId()
    {
        $parent = User::doesntHave('parent')->first();
        $child = User::has('parent')->first();

        $parent->parent()->associate($child->id);

        $this->assertEquals($child->id, $parent->parent_id);
        $this->assertEquals($child->id, $parent->parent->id);
    }

    public function testAssociateWithIdUnsetsLoadedRelation()
    {
        $child = User::has('parent')->with('parent')->first();

        // Overwrite the (loaded) parent relation
        $child->parent()->associate($child->id);

        $this->assertEquals($child->id, $child->parent_id);
        $this->assertFalse($child->relationLoaded('parent'));
    }
}

class User extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function parentBySlug()
    {
        return $this->belongsTo(self::class, 'parent_slug', 'slug');
    }
}
