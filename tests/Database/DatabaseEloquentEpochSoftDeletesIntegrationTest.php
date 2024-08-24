<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\EpochSoftDeletes;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentEpochSoftDeletesIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->timestamps();
            $table->epochSoftDeletes();

            $table->unique(['email', 'deleted_at']);
        });
    }

    public function testSoftDeletesStoreEpochTimestamp()
    {
        Carbon::setTestNow($now = Carbon::now());
        $this->createUsers();

        $user = EpochSoftDeletesTestUser::find(1);
        $user->delete();

        $this->assertEquals($now->timestamp, $user->getAttributes()['deleted_at']);
        $this->assertInstanceOf(Carbon::class, $user->getOriginal('deleted_at'));
    }

    public function testSoftDeletesAreNotRetrieved()
    {
        $this->createUsers();

        $user = EpochSoftDeletesTestUser::find(1);
        $user->delete();

        $users = EpochSoftDeletesTestUser::all();
        $this->assertCount(2, $users);

        $trashedUsers = EpochSoftDeletesTestUser::withTrashed()->get();
        $this->assertCount(3, $trashedUsers);
    }

    public function testRestoringSoftDeletedItem()
    {
        $this->createUsers();

        $user = EpochSoftDeletesTestUser::find(1);
        $user->delete();
        $user->restore();

        $this->assertEquals(0, $user->getAttributes()['deleted_at']);

        $restoredUser = EpochSoftDeletesTestUser::find(1);
        $this->assertNotNull($restoredUser);
    }

    public function testUniqueConstraintsAfterSoftDelete()
    {
        $this->createUsers();

        $user = EpochSoftDeletesTestUser::find(1);
        $user->delete();

        $duplicateUser = new EpochSoftDeletesTestUser();
        $duplicateUser->email = $user->email;

        $this->expectNotToPerformAssertions();
        $duplicateUser->save();
    }

    /**
     * Helpers...
     *
     * @return EpochSoftDeletesTestUser[]
     */
    protected function createUsers()
    {
        $taylor = EpochSoftDeletesTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        $abigail = EpochSoftDeletesTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);
        $ismayil = EpochSoftDeletesTestUser::create(['id' => 3, 'email' => 'me@ismayil.dev']);

        return [$taylor, $abigail, $ismayil];
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

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }
}

/**
 * Eloquent Models...
 */
class EpochSoftDeletesTestUser extends Eloquent
{
    use EpochSoftDeletes;

    protected $table = 'users';
    protected $guarded = [];
}
