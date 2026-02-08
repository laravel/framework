<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentSoftDeletesCustomValueTest extends TestCase
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
     * Create the database schema.
     */
    public function createSchema(): void
    {
        $this->schema()->create('custom_soft_delete_users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->timestamps();
            // deleted_at uses a custom default value instead of null
            $table->dateTime('deleted_at')->default('9999-12-31 23:59:59');
        });
    }

    /**
     * Tear down the database schema.
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow(null);

        $this->schema()->drop('custom_soft_delete_users');

        parent::tearDown();
    }

    /**
     * Get a schema builder instance.
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    /**
     * Get a database connection instance.
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Create users for testing.
     */
    protected function createUsers(): array
    {
        $taylor = CustomSoftDeleteUser::create(['email' => 'taylorotwell@gmail.com']);
        $taylor->delete(); // Soft delete

        $abigail = CustomSoftDeleteUser::create(['email' => 'abigailotwell@gmail.com']);

        return [$taylor, $abigail];
    }

    public function testModelUsingCustomUndeletedValue()
    {
        $user = CustomSoftDeleteUser::create(['email' => 'test@example.com']);

        // Check if the custom undeleted value is set when not deleted
        $this->assertEquals('9999-12-31 23:59:59', $user->deleted_at);
        $this->assertFalse($user->trashed());
    }

    public function testTrashedMethodWithCustomUndeletedValue()
    {
        $user = CustomSoftDeleteUser::create(['email' => 'test@example.com']);
        $this->assertFalse($user->trashed());

        $user->delete();

        $this->assertTrue($user->trashed());
        $this->assertNotEquals('9999-12-31 23:59:59', $user->deleted_at);
    }

    public function testRestoreWithCustomUndeletedValue()
    {
        $user = CustomSoftDeleteUser::create(['email' => 'test@example.com']);
        $user->delete();

        $this->assertTrue($user->trashed());

        $user->restore();

        $this->assertFalse($user->trashed());
        $this->assertEquals('9999-12-31 23:59:59', $user->deleted_at);
    }

    public function testQueryScopesWithCustomUndeletedValue()
    {
        $this->createUsers();

        // Get only non-deleted records by default
        $users = CustomSoftDeleteUser::all();
        $this->assertCount(1, $users);
        $this->assertEquals('abigailotwell@gmail.com', $users->first()->email);

        // Get all records including trashed ones with withTrashed()
        $allUsers = CustomSoftDeleteUser::withTrashed()->get();
        $this->assertCount(2, $allUsers);

        // Get only trashed records with onlyTrashed()
        $trashedUsers = CustomSoftDeleteUser::onlyTrashed()->get();
        $this->assertCount(1, $trashedUsers);
        $this->assertEquals('taylorotwell@gmail.com', $trashedUsers->first()->email);
    }

    public function testBuilderRestoreMacro()
    {
        [$taylor, $abigail] = $this->createUsers();

        // Restore via builder
        CustomSoftDeleteUser::withTrashed()->where('email', 'taylorotwell@gmail.com')->restore();

        $taylor = CustomSoftDeleteUser::find($taylor->id);
        $this->assertNotNull($taylor);
        $this->assertFalse($taylor->trashed());
        $this->assertEquals('9999-12-31 23:59:59', $taylor->deleted_at);
    }
}

class CustomSoftDeleteUser extends Eloquent
{
    use SoftDeletes;

    protected $table = 'custom_soft_delete_users';
    protected $guarded = [];

    /**
     * Set the value indicating the model is not soft deleted.
     */
    protected $undeletedValue = '9999-12-31 23:59:59';
}
