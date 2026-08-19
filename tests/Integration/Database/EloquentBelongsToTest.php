<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToTest;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\App\Models\Relationships\SelfReferencingUser;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentBelongsToTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->nullable();
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('parent_slug')->nullable();
        });

        $user = SelfReferencingUser::create(['slug' => Str::random()]);
        SelfReferencingUser::create(['parent_id' => $user->id, 'parent_slug' => $user->slug]);
    }

    public function testHasSelf()
    {
        $users = SelfReferencingUser::has('parent')->get();

        $this->assertCount(1, $users);
    }

    public function testHasSelfCustomOwnerKey()
    {
        $users = SelfReferencingUser::has('parentBySlug')->get();

        $this->assertCount(1, $users);
    }

    public function testAssociateWithModel()
    {
        $parent = SelfReferencingUser::doesntHave('parent')->first();
        $child = SelfReferencingUser::has('parent')->first();

        $parent->parent()->associate($child);

        $this->assertEquals($child->id, $parent->parent_id);
        $this->assertEquals($child->id, $parent->parent->id);
    }

    public function testAssociateWithId()
    {
        $parent = SelfReferencingUser::doesntHave('parent')->first();
        $child = SelfReferencingUser::has('parent')->first();

        $parent->parent()->associate($child->id);

        $this->assertEquals($child->id, $parent->parent_id);
        $this->assertEquals($child->id, $parent->parent->id);
    }

    public function testAssociateWithIdUnsetsLoadedRelation()
    {
        $child = SelfReferencingUser::has('parent')->with('parent')->first();

        // Overwrite the (loaded) parent relation
        $child->parent()->associate($child->id);

        $this->assertEquals($child->id, $child->parent_id);
        $this->assertFalse($child->relationLoaded('parent'));
    }

    public function testParentIsNotNull()
    {
        $child = SelfReferencingUser::has('parent')->first();
        $parent = null;

        $this->assertFalse($child->parent()->is($parent));
        $this->assertTrue($child->parent()->isNot($parent));
    }

    public function testParentIsModel()
    {
        $child = SelfReferencingUser::has('parent')->first();
        $parent = SelfReferencingUser::doesntHave('parent')->first();

        $this->assertTrue($child->parent()->is($parent));
        $this->assertFalse($child->parent()->isNot($parent));
    }

    public function testParentIsNotAnotherModel()
    {
        $child = SelfReferencingUser::has('parent')->first();
        $parent = new SelfReferencingUser;
        $parent->id = 3;

        $this->assertFalse($child->parent()->is($parent));
        $this->assertTrue($child->parent()->isNot($parent));
    }

    public function testNullParentIsNotModel()
    {
        $child = SelfReferencingUser::has('parent')->first();
        $child->parent()->dissociate();
        $parent = SelfReferencingUser::doesntHave('parent')->first();

        $this->assertFalse($child->parent()->is($parent));
        $this->assertTrue($child->parent()->isNot($parent));
    }

    public function testParentIsNotModelWithAnotherTable()
    {
        $child = SelfReferencingUser::has('parent')->first();
        $parent = SelfReferencingUser::doesntHave('parent')->first();
        $parent->setTable('foo');

        $this->assertFalse($child->parent()->is($parent));
        $this->assertTrue($child->parent()->isNot($parent));
    }

    public function testParentIsNotModelWithAnotherConnection()
    {
        $child = SelfReferencingUser::has('parent')->first();
        $parent = SelfReferencingUser::doesntHave('parent')->first();
        $parent->setConnection('foo');

        $this->assertFalse($child->parent()->is($parent));
        $this->assertTrue($child->parent()->isNot($parent));
    }
}
