<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class DatabaseEloquentBelongsToManySoftDeletableTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->softDeletes();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->softDeletes();
        });

        Schema::create('group_member', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_id')->unsigned();
            $table->foreign('group_id')->references('id')->on('groups');
            $table->integer('member_id')->unsigned();
            $table->foreign('member_id')->references('id')->on('members');
            $table->softDeletes();
        });

        $this->seedBaseData();
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Schema::drop('groups');
        Schema::drop('members');
        Schema::drop('group_member');
    }

    /**
     * Helpers...
     */
    protected function seedBaseData()
    {
        DB::table('groups')->insert(['id' => 1, 'name' => 'Testgroup']);
        DB::table('members')->insert([
            ['id' => 1, 'name' => 'Testmember 1'],
            ['id' => 2, 'name' => 'Testmember 2'],
            ['id' => 3, 'name' => 'Testmember 3']
        ]);
    }

    protected function seedActiveGroupMembers()
    {
        BelongsToManySoftDeletableTestTestGroupMember::query()->insert([
            ['id' => 1, 'group_id' => 1, 'member_id' => 1, 'deleted_at' => null],
            ['id' => 2, 'group_id' => 1, 'member_id' => 2, 'deleted_at' => null],
            ['id' => 3, 'group_id' => 1, 'member_id' => 3, 'deleted_at' => null],
        ]);
    }

    protected function seedSoftDeletedGroupMembers()
    {
        BelongsToManySoftDeletableTestTestGroupMember::query()->insert([
            ['id' => 1, 'group_id' => 1, 'member_id' => 1, 'deleted_at' => new \DateTimeImmutable()],
            ['id' => 2, 'group_id' => 1, 'member_id' => 2, 'deleted_at' => new \DateTimeImmutable()],
            ['id' => 3, 'group_id' => 1, 'member_id' => 3, 'deleted_at' => new \DateTimeImmutable()],
        ]);
    }

    protected function seedMixedGroupMembers()
    {
        BelongsToManySoftDeletableTestTestGroupMember::query()->insert([
            ['id' => 1, 'group_id' => 1, 'member_id' => 1, 'deleted_at' => null],
            ['id' => 2, 'group_id' => 1, 'member_id' => 2, 'deleted_at' => null],
            ['id' => 3, 'group_id' => 1, 'member_id' => 3, 'deleted_at' => new \DateTimeImmutable()],
        ]);
    }

    /**
     * @dataProvider groupProvider
     */
    public function testQueryPivotsWithTrashed($group)
    {
        $this->seedMixedGroupMembers();

        $group = ($group)::find(1);
        $this->assertEquals(3, $group->members()->pivotWithTrashed()->count());
        $this->assertEquals([1, 2, 3], array_values($group->members()->pivotWithTrashed()->get()->modelKeys()));
    }

    /**
     * @dataProvider groupProvider
     */
    public function testQueryPivotsWithoutTrashed($group)
    {
        $this->seedMixedGroupMembers();

        $group = ($group)::find(1);
        $this->assertEquals(2, $group->members()->pivotWithoutTrashed()->count());
        $this->assertEquals([1, 2], array_values($group->members()->pivotWithoutTrashed()->get()->modelKeys()));
    }

    /**
     * @dataProvider groupProvider
     */
    public function testQueryPivotsOnlyTrashed($group)
    {
        $this->seedMixedGroupMembers();

        $group = ($group)::find(1);
        $this->assertEquals(1, $group->members()->pivotOnlyTrashed()->count());
        $this->assertEquals([3], array_values($group->members()->pivotOnlyTrashed()->get()->modelKeys()));
    }

    /**
     * @dataProvider groupProvider
     */
    public function testAttachDeletedSingleId($group)
    {
        $this->seedSoftDeletedGroupMembers();

        $group = ($group)::find(1);
        $group->members()->attach(1);

        $this->assertDatabaseHas('group_member', ['id' => 1, 'group_id' => 1, 'member_id' => 1, 'deleted_at' => null]);
        $this->assertSoftDeleted('group_member', ['id' => 2, 'group_id' => 1, 'member_id' => 2]);
        $this->assertSoftDeleted('group_member', ['id' => 3, 'group_id' => 1, 'member_id' => 3]);
    }

    /**
     * @dataProvider groupProvider
     */
    public function testAttachDeletedMultipleIds($group)
    {
        $this->seedSoftDeletedGroupMembers();

        $group = ($group)::find(1);
        $group->members()->attach([2, 3]);

        $this->assertSoftDeleted('group_member', ['id' => 1, 'group_id' => 1, 'member_id' => 1]);
        $this->assertDatabaseHas('group_member', ['id' => 2, 'group_id' => 1, 'member_id' => 2, 'deleted_at' => null]);
        $this->assertDatabaseHas('group_member', ['id' => 3, 'group_id' => 1, 'member_id' => 3, 'deleted_at' => null]);
    }

    /**
     * @dataProvider groupProvider
     */
    public function testDetachActiveSingleId($group)
    {
        $this->seedActiveGroupMembers();

        $group = ($group)::find(1);
        $group->members()->detach(1);

        $this->assertSoftDeleted('group_member', ['id' => 1, 'group_id' => 1, 'member_id' => 1]);
        $this->assertDatabaseHas('group_member', ['id' => 2, 'group_id' => 1, 'member_id' => 2, 'deleted_at' => null]);
        $this->assertDatabaseHas('group_member', ['id' => 3, 'group_id' => 1, 'member_id' => 3, 'deleted_at' => null]);
    }

    /**
     * @dataProvider groupProvider
     */
    public function testDetachActiveMultipleIds($group)
    {
        $this->seedActiveGroupMembers();

        $group = ($group)::find(1);
        $group->members()->detach([1, 2]);

        $this->assertSoftDeleted('group_member', ['id' => 1, 'group_id' => 1, 'member_id' => 1]);
        $this->assertSoftDeleted('group_member', ['id' => 2, 'group_id' => 1, 'member_id' => 2]);
        $this->assertDatabaseHas('group_member', ['id' => 3, 'group_id' => 1, 'member_id' => 3, 'deleted_at' => null]);
    }

    /**
     * @dataProvider groupProvider
     */
    public function testSyncDetach($group)
    {
        $this->seedActiveGroupMembers();

        $group = ($group)::find(1);
        $group->members()->sync([1, 2]);

        $this->assertDatabaseHas('group_member', ['id' => 1, 'group_id' => 1, 'member_id' => 1, 'deleted_at' => null]);
        $this->assertDatabaseHas('group_member', ['id' => 2, 'group_id' => 1, 'member_id' => 2, 'deleted_at' => null]);
        $this->assertSoftDeleted('group_member', ['id' => 3, 'group_id' => 1, 'member_id' => 3]);
    }

    /**
     * @dataProvider groupProvider
     */
    public function testSyncWithoutDetaching($group)
    {
        $this->seedMixedGroupMembers();

        $group = ($group)::find(1);
        $group->members()->syncWithoutDetaching([1, 3]);

        $this->assertDatabaseHas('group_member', ['id' => 1, 'group_id' => 1, 'member_id' => 1, 'deleted_at' => null]);
        $this->assertDatabaseHas('group_member', ['id' => 2, 'group_id' => 1, 'member_id' => 2, 'deleted_at' => null]);
        $this->assertDatabaseHas('group_member', ['id' => 3, 'group_id' => 1, 'member_id' => 3, 'deleted_at' => null]);
    }

    /**
     * @dataProvider groupProvider
     */
    public function testSyncRestore($group)
    {
        $this->seedSoftDeletedGroupMembers();

        $group = ($group)::find(1);
        $group->members()->sync([1, 2]);

        $this->assertDatabaseHas('group_member', ['id' => 1, 'group_id' => 1, 'member_id' => 1, 'deleted_at' => null]);
        $this->assertDatabaseHas('group_member', ['id' => 2, 'group_id' => 1, 'member_id' => 2, 'deleted_at' => null]);
        $this->assertSoftDeleted('group_member', ['id' => 3, 'group_id' => 1, 'member_id' => 3]);
    }

    public function groupProvider()
    {
        return [
            [BelongsToManySoftDeletableTestTestGroup::class],
            [BelongsToManySoftDeletableTestUsingTestGroup::class]
        ];
    }
}

class BelongsToManySoftDeletableTestTestGroup extends Eloquent
{

    use SoftDeletes;
    protected $table = 'groups';
    protected $fillable = ['id', 'name'];
    public $timestamps = false;

    public function members()
    {
        return $this
            ->belongsToMany(
                BelongsToManySoftDeletableTestTestMember::class,
                BelongsToManySoftDeletableTestTestGroupMember::class,
                'group_id',
                'member_id'
            );
    }
}

class BelongsToManySoftDeletableTestUsingTestGroup extends Eloquent
{

    use SoftDeletes;
    protected $table = 'groups';
    protected $fillable = ['id', 'name'];
    public $timestamps = false;

    public function members()
    {
        return $this
            ->belongsToMany(
                BelongsToManySoftDeletableTestTestMember::class,
                'group_member',
                'group_id',
                'member_id'
            )
            ->using(BelongsToManySoftDeletableTestTestGroupMember::class);
    }
}

class BelongsToManySoftDeletableTestTestMember extends Eloquent
{
    use SoftDeletes;
    protected $table = 'members';
    protected $fillable = ['id', 'name'];
    public $timestamps = false;

    public function groups()
    {
        return $this->belongsToMany(
            BelongsToManySoftDeletableTestTestGroup::class,
            BelongsToManySoftDeletableTestTestGroupMember::class,
            'member_id',
            'group_id'
        );
    }
}

class BelongsToManySoftDeletableTestTestGroupMember extends Pivot implements SoftDeletable
{
    use SoftDeletes;
    protected $table = 'group_member';
    protected $fillable = ['id', 'group_id', 'member_id'];
    public $timestamps = false;

    public function groups(){
        return $this->hasMany(BelongsToManySoftDeletableTestTestGroup::class);
    }

    public function members(){
        return $this->hasMany(BelongsToManySoftDeletableTestTestMember::class);
    }
}
