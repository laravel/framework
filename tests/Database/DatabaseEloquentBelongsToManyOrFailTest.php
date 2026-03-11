<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyOrFailTest extends TestCase
{
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

    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
        });

        $this->schema()->create('roles', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->schema()->create('role_user', function ($table) {
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->boolean('active')->default(false);
        });
    }

    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('roles');
        $this->schema()->drop('role_user');
    }

    protected function seedData()
    {
        OrFailUser::create(['id' => 1, 'email' => 'taylor@laravel.com']);
        OrFailRole::insert([
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Editor'],
            ['id' => 3, 'name' => 'Viewer'],
        ]);
    }

    public function testSyncOrFail()
    {
        $this->seedData();

        $user = OrFailUser::find(1);

        $result = $user->roles()->syncOrFail([1, 2]);

        $this->assertEquals([1, 2], $result['attached']);
        $this->assertEmpty($result['detached']);
        $this->assertEmpty($result['updated']);
        $this->assertCount(2, $user->roles);
    }

    public function testSyncWithoutDetachingOrFail()
    {
        $this->seedData();

        $user = OrFailUser::find(1);
        $user->roles()->attach([1]);

        $result = $user->roles()->syncWithoutDetachingOrFail([2, 3]);

        $this->assertEquals([2, 3], $result['attached']);
        $this->assertEmpty($result['detached']);
        $this->assertCount(3, $user->roles()->get());
    }

    public function testAttachOrFail()
    {
        $this->seedData();

        $user = OrFailUser::find(1);

        $user->roles()->attachOrFail(1);

        $this->assertCount(1, $user->roles);
    }

    public function testAttachOrFailWithAttributes()
    {
        $this->seedData();

        $user = OrFailUser::find(1);
        $user->roles()->attachOrFail(1, ['active' => true]);

        $pivot = DB::table('role_user')->where('user_id', 1)->where('role_id', 1)->first();
        $this->assertEquals(1, $pivot->active);
    }

    public function testDetachOrFail()
    {
        $this->seedData();

        $user = OrFailUser::find(1);
        $user->roles()->attach([1, 2, 3]);

        $result = $user->roles()->detachOrFail([1, 2]);

        $this->assertEquals(2, $result);
        $this->assertCount(1, $user->roles()->get());
    }

    public function testDetachOrFailAll()
    {
        $this->seedData();

        $user = OrFailUser::find(1);
        $user->roles()->attach([1, 2, 3]);

        $result = $user->roles()->detachOrFail();

        $this->assertEquals(3, $result);
        $this->assertCount(0, $user->roles()->get());
    }

    public function testToggleOrFail()
    {
        $this->seedData();

        $user = OrFailUser::find(1);
        $user->roles()->attach([1]);

        $result = $user->roles()->toggleOrFail([1, 2]);

        $this->assertEquals([1], $result['detached']);
        $this->assertEquals([2], $result['attached']);
        $this->assertCount(1, $user->roles()->get());
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

class OrFailUser extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(OrFailRole::class, 'role_user', 'user_id', 'role_id');
    }
}

class OrFailRole extends Eloquent
{
    protected $table = 'roles';
    protected $guarded = [];
    public $timestamps = false;
}
