<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;
use LogicException;

class DatabaseEloquentWithDefaultBehaviorTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
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

    protected function createSchema(): void
    {
        $this->schema()->create('businesses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('wallets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('holder_id')->nullable();
            $table->string('holder_type')->nullable();
            $table->integer('balance')->default(0);
            $table->timestamps();
        });

        $this->schema()->create('profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('businesses');
        $this->schema()->drop('wallets');
        $this->schema()->drop('profiles');
        $this->schema()->drop('users');
    }

    public function testWithDefaultReturnsUnsavedModelInstance()
    {
        $business = Business::create(['name' => 'Acme Inc.']);

        $wallet = $business->wallet;

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertFalse($wallet->exists);
        $this->assertSame(0, $wallet->balance);
    }

    public function testSavingUnsavedDefaultModelWithoutKeysThrowsException()
    {
        $business = Business::create(['name' => 'Acme Inc.']);

        $this->expectException(LogicException::class);
        $business->wallet->save();
    }

    public function testDefaultModelCanBeSavedIfForeignKeysAreSet()
    {
        $business = Business::create(['name' => 'Acme Inc.']);

        $wallet = $business->wallet;
        $wallet->holder_id = $business->id;
        $wallet->holder_type = Business::class;

        $this->assertTrue($wallet->save());
        $this->assertTrue($wallet->exists);
    }

    public function testNormalSavedModelHasNoIssues()
    {
        $business = Business::create(['name' => 'Acme Inc.']);

        $wallet = new Wallet([
            'balance' => 50,
            'holder_id' => $business->id,
            'holder_type' => Business::class,
        ]);

        $wallet->save();

        $this->assertTrue($wallet->exists);
        $this->assertEquals(50, $wallet->balance);
    }

    public function testWithoutAutoEagerLoadingWithDefaultDoesNotTriggerSaveIssue()
    {
        $business = BusinessWithoutAutoLoad::create(['name' => 'Manual Corp']);

        $wallet = $business->wallet;

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertFalse($wallet->exists);
        $this->assertSame(0, $wallet->balance);

        $this->expectException(LogicException::class);
        $wallet->save();
    }

    public function testObserverCannotSaveDefaultModelWithMissingKeys()
    {
        BusinessWithObserver::observe(new class {
            public function created(BusinessWithObserver $business)
            {
                $business->wallet->save();
            }
        });

        $this->expectException(LogicException::class);

        BusinessWithObserver::create(['name' => 'Danger Zone']);
    }

    public function testBelongsToWithDefaultThrowsWhenSavingWithoutKeys()
    {
        $profile = new Profile();
        $profile->save();

        $user = $profile->user;

        $this->assertInstanceOf(TestUserModel::class, $user);
        $this->assertFalse($user->exists);

        $this->expectException(LogicException::class);
        $user->save();
    }

    public function testTouchingDefaultModelDoesNotThrow()
    {
        $business = Business::create(['name' => 'Touchables']);

        $wallet = $business->wallet;

        $this->assertFalse($wallet->exists);
        $this->assertTrue($wallet->touch());
    }

    public function testMorphOneWithoutWithDefaultReturnsNull()
    {
        $business = BusinessWithoutDefault::create(['name' => 'Legacy Ltd.']);

        $this->assertNull($business->wallet);
        $this->assertNull(optional($business->wallet)->balance);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}

class Business extends Model
{
    protected $table = 'businesses';
    protected $guarded = [];

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'holder')->withDefault([
            'balance' => 0,
        ]);
    }
}

class BusinessWithoutAutoLoad extends Model
{
    protected $table = 'businesses';
    protected $guarded = [];

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'holder')->withDefault([
            'balance' => 0,
        ]);
    }
}

class BusinessWithObserver extends Model
{
    protected $table = 'businesses';
    protected $guarded = [];

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'holder')->withDefault([
            'balance' => 0,
        ]);
    }
}

class BusinessWithoutDefault extends Model
{
    protected $table = 'businesses';
    protected $guarded = [];

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'holder');
    }
}

class Wallet extends Model
{
    protected $table = 'wallets';
    protected $guarded = [];

    public function holder()
    {
        return $this->morphTo();
    }
}

class Profile extends Model
{
    protected $table = 'profiles';
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TestUserModel::class, 'user_id')->withDefault([
            'name' => 'Guest',
        ]);
    }
}

class TestUserModel extends Model
{
    protected $table = 'users';
    protected $guarded = [];
}
