<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseEloquentTimestampsTest extends TestCase
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
            $table->string('email')->unique();
            $table->timestamps();
        });

        $this->schema()->create('users_created_at', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('created_at');
        });

        $this->schema()->create('users_updated_at', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('updated_at');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('users_created_at');
        $this->schema()->drop('users_updated_at');
        Carbon::setTestNow(null);
    }

    /**
     * Tests...
     */
    public function testUserWithCreatedAtAndUpdatedAt()
    {
        Carbon::setTestNow($now = Carbon::now());

        $user = UserWithCreatedAndUpdated::create([
            'email' => 'test@test.com',
        ]);

        $this->assertEquals($now->toDateTimeString(), $user->created_at->toDateTimeString());
        $this->assertEquals($now->toDateTimeString(), $user->updated_at->toDateTimeString());
    }

    public function testUserWithCreatedAt()
    {
        Carbon::setTestNow($now = Carbon::now());

        $user = UserWithCreated::create([
            'email' => 'test@test.com',
        ]);

        $this->assertEquals($now->toDateTimeString(), $user->created_at->toDateTimeString());
    }

    public function testUserWithUpdatedAt()
    {
        Carbon::setTestNow($now = Carbon::now());

        $user = UserWithUpdated::create([
            'email' => 'test@test.com',
        ]);

        $this->assertEquals($now->toDateTimeString(), $user->updated_at->toDateTimeString());
    }

    public function testWithoutTimestamp()
    {
        Carbon::setTestNow($now = Carbon::now()->setYear(1995)->startOfYear());
        $user = UserWithCreatedAndUpdated::create(['email' => 'foo@example.com']);
        Carbon::setTestNow(Carbon::now()->addHour());

        $this->assertTrue($user->usesTimestamps());

        $user->withoutTimestamps(function () use ($user) {
            $this->assertFalse($user->usesTimestamps());

            $user->withoutTimestamps(function () use ($user) {
                $this->assertFalse($user->usesTimestamps());
            });

            $this->assertFalse($user->usesTimestamps());
            $user->update([
                'email' => 'bar@example.com',
            ]);
        });

        $this->assertTrue($user->usesTimestamps());
        $this->assertTrue($now->equalTo($user->updated_at));
        $this->assertSame('bar@example.com', $user->email);
    }

    public function testWithoutTimestampWhenAlreadyIgnoringTimestamps()
    {
        Carbon::setTestNow($now = Carbon::now()->setYear(1995)->startOfYear());
        $user = UserWithCreatedAndUpdated::create(['email' => 'foo@example.com']);
        Carbon::setTestNow(Carbon::now()->addHour());

        $user->timestamps = false;

        $this->assertFalse($user->usesTimestamps());

        $user->withoutTimestamps(function () use ($user) {
            $this->assertFalse($user->usesTimestamps());
            $user->update([
                'email' => 'bar@example.com',
            ]);
        });

        $this->assertFalse($user->usesTimestamps());
        $this->assertTrue($now->equalTo($user->updated_at));
        $this->assertSame('bar@example.com', $user->email);
    }

    public function testWithoutTimestampRestoresWhenClosureThrowsException()
    {
        $user = UserWithCreatedAndUpdated::create(['email' => 'foo@example.com']);

        $user->timestamps = true;

        try {
            $user->withoutTimestamps(function () use ($user) {
                $this->assertFalse($user->usesTimestamps());
                throw new RuntimeException();
            });
            $this->fail();
        } catch (RuntimeException) {
            //
        }

        $this->assertTrue($user->timestamps);
    }

    public function testWithoutTimestampsRespectsClasses()
    {
        $a = new UserWithCreatedAndUpdated();
        $b = new UserWithCreatedAndUpdated();
        $z = new UserWithUpdated();

        $this->assertTrue($a->usesTimestamps());
        $this->assertTrue($b->usesTimestamps());
        $this->assertTrue($z->usesTimestamps());
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));

        Eloquent::withoutTimestamps(function () use ($a, $b, $z) {
            $this->assertFalse($a->usesTimestamps());
            $this->assertFalse($b->usesTimestamps());
            $this->assertFalse($z->usesTimestamps());
            $this->assertTrue(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
            $this->assertTrue(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));
        });

        $this->assertTrue($a->usesTimestamps());
        $this->assertTrue($b->usesTimestamps());
        $this->assertTrue($z->usesTimestamps());
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));

        UserWithCreatedAndUpdated::withoutTimestamps(function () use ($a, $b, $z) {
            $this->assertFalse($a->usesTimestamps());
            $this->assertFalse($b->usesTimestamps());
            $this->assertTrue($z->usesTimestamps());
            $this->assertTrue(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
            $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));
        });

        $this->assertTrue($a->usesTimestamps());
        $this->assertTrue($b->usesTimestamps());
        $this->assertTrue($z->usesTimestamps());
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));

        UserWithUpdated::withoutTimestamps(function () use ($a, $b, $z) {
            $this->assertTrue($a->usesTimestamps());
            $this->assertTrue($b->usesTimestamps());
            $this->assertFalse($z->usesTimestamps());
            $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
            $this->assertTrue(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));
        });

        $this->assertTrue($a->usesTimestamps());
        $this->assertTrue($b->usesTimestamps());
        $this->assertTrue($z->usesTimestamps());
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));

        Eloquent::withoutTimestampsOn([], function () use ($a, $b, $z) {
            $this->assertTrue($a->usesTimestamps());
            $this->assertTrue($b->usesTimestamps());
            $this->assertTrue($z->usesTimestamps());
            $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
            $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));
        });

        $this->assertTrue($a->usesTimestamps());
        $this->assertTrue($b->usesTimestamps());
        $this->assertTrue($z->usesTimestamps());
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));

        Eloquent::withoutTimestampsOn([UserWithCreatedAndUpdated::class], function () use ($a, $b, $z) {
            $this->assertFalse($a->usesTimestamps());
            $this->assertFalse($b->usesTimestamps());
            $this->assertTrue($z->usesTimestamps());
            $this->assertTrue(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
            $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));
        });

        $this->assertTrue($a->usesTimestamps());
        $this->assertTrue($b->usesTimestamps());
        $this->assertTrue($z->usesTimestamps());
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));

        Eloquent::withoutTimestampsOn([UserWithUpdated::class], function () use ($a, $b, $z) {
            $this->assertTrue($a->usesTimestamps());
            $this->assertTrue($b->usesTimestamps());
            $this->assertFalse($z->usesTimestamps());
            $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
            $this->assertTrue(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));
        });

        $this->assertTrue($a->usesTimestamps());
        $this->assertTrue($b->usesTimestamps());
        $this->assertTrue($z->usesTimestamps());
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));

        Eloquent::withoutTimestampsOn([UserWithCreatedAndUpdated::class, UserWithUpdated::class], function () use ($a, $b, $z) {
            $this->assertFalse($a->usesTimestamps());
            $this->assertFalse($b->usesTimestamps());
            $this->assertFalse($z->usesTimestamps());
            $this->assertTrue(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
            $this->assertTrue(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));
        });

        $this->assertTrue($a->usesTimestamps());
        $this->assertTrue($b->usesTimestamps());
        $this->assertTrue($z->usesTimestamps());
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithCreatedAndUpdated::class));
        $this->assertFalse(Eloquent::isIgnoringTimestamps(UserWithUpdated::class));
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

/**
 * Eloquent Models...
 */
class UserWithCreatedAndUpdated extends Eloquent
{
    protected $table = 'users';

    protected $guarded = [];
}

class UserWithCreated extends Eloquent
{
    public const UPDATED_AT = null;

    protected $table = 'users_created_at';

    protected $guarded = [];

    protected $dateFormat = 'U';
}

class UserWithUpdated extends Eloquent
{
    public const CREATED_AT = null;

    protected $table = 'users_updated_at';

    protected $guarded = [];

    protected $dateFormat = 'U';
}
