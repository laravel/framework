<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasOneThroughIntegrationTest extends TestCase
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
            $table->unsignedInteger('position_id')->unique()->nullable();
            $table->string('position_short');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->schema()->create('contracts', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unique();
            $table->string('title');
            $table->text('body');
            $table->string('email');
            $table->timestamps();
        });

        $this->schema()->create('positions', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('shortname');
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
        $this->schema()->drop('users');
        $this->schema()->drop('contracts');
        $this->schema()->drop('positions');
    }

    public function testItLoadsAHasOneThroughRelationWithCustomKeys()
    {
        $this->seedData();
        $contract = HasOneThroughTestPosition::first()->contract;

        $this->assertSame('A title', $contract->title);
    }

    public function testItLoadsADefaultHasOneThroughRelation()
    {
        $this->migrateDefault();
        $this->seedDefaultData();

        $contract = HasOneThroughDefaultTestPosition::first()->contract;
        $this->assertSame('A title', $contract->title);
        $this->assertArrayNotHasKey('email', $contract->getAttributes());

        $this->resetDefault();
    }

    public function testItLoadsARelationWithCustomIntermediateAndLocalKey()
    {
        $this->seedData();
        $contract = HasOneThroughIntermediateTestPosition::first()->contract;

        $this->assertSame('A title', $contract->title);
    }

    public function testEagerLoadingARelationWithCustomIntermediateAndLocalKey()
    {
        $this->seedData();
        $contract = HasOneThroughIntermediateTestPosition::with('contract')->first()->contract;

        $this->assertSame('A title', $contract->title);
    }

    public function testWhereHasOnARelationWithCustomIntermediateAndLocalKey()
    {
        $this->seedData();
        $position = HasOneThroughIntermediateTestPosition::whereHas('contract', function ($query) {
            $query->where('title', 'A title');
        })->get();

        $this->assertCount(1, $position);
    }

    public function testWithWhereHasOnARelationWithCustomIntermediateAndLocalKey()
    {
        $this->seedData();
        $position = HasOneThroughIntermediateTestPosition::withWhereHas('contract', function ($query) {
            $query->where('title', 'A title');
        })->get();

        $this->assertCount(1, $position);
        $this->assertTrue($position->first()->relationLoaded('contract'));
        $this->assertEquals($position->first()->contract->pluck('title')->unique()->toArray(), ['A title']);
    }

    public function testFirstOrFailThrowsAnException()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [Illuminate\Tests\Database\HasOneThroughTestContract].');

        HasOneThroughTestPosition::create(['id' => 1, 'name' => 'President', 'shortname' => 'ps'])
            ->user()->create(['id' => 1, 'email' => 'taylorotwell@gmail.com', 'position_short' => 'ps']);

        HasOneThroughTestPosition::first()->contract()->firstOrFail();
    }

    public function testFindOrFailThrowsAnException()
    {
        $this->expectException(ModelNotFoundException::class);

        HasOneThroughTestPosition::create(['id' => 1, 'name' => 'President', 'shortname' => 'ps'])
            ->user()->create(['id' => 1, 'email' => 'taylorotwell@gmail.com', 'position_short' => 'ps']);

        HasOneThroughTestPosition::first()->contract()->findOrFail(1);
    }

    public function testFirstRetrievesFirstRecord()
    {
        $this->seedData();
        $contract = HasOneThroughTestPosition::first()->contract()->first();

        $this->assertNotNull($contract);
        $this->assertSame('A title', $contract->title);
    }

    public function testAllColumnsAreRetrievedByDefault()
    {
        $this->seedData();
        $contract = HasOneThroughTestPosition::first()->contract()->first();
        $this->assertEquals([
            'id',
            'user_id',
            'title',
            'body',
            'email',
            'created_at',
            'updated_at',
            'laravel_through_key',
        ], array_keys($contract->getAttributes()));
    }

    public function testOnlyProperColumnsAreSelectedIfProvided()
    {
        $this->seedData();
        $contract = HasOneThroughTestPosition::first()->contract()->first(['title', 'body']);

        $this->assertEquals([
            'title',
            'body',
            'laravel_through_key',
        ], array_keys($contract->getAttributes()));
    }

    public function testChunkReturnsCorrectModels()
    {
        $this->seedData();
        $this->seedDataExtended();
        $position = HasOneThroughTestPosition::find(1);

        $position->contract()->chunk(10, function ($contractsChunk) {
            $contract = $contractsChunk->first();
            $this->assertEquals([
                'id',
                'user_id',
                'title',
                'body',
                'email',
                'created_at',
                'updated_at',
                'laravel_through_key', ], array_keys($contract->getAttributes()));
        });
    }

    public function testCursorReturnsCorrectModels()
    {
        $this->seedData();
        $this->seedDataExtended();
        $position = HasOneThroughTestPosition::find(1);

        $contracts = $position->contract()->cursor();

        foreach ($contracts as $contract) {
            $this->assertEquals([
                'id',
                'user_id',
                'title',
                'body',
                'email',
                'created_at',
                'updated_at',
                'laravel_through_key', ], array_keys($contract->getAttributes()));
        }
    }

    public function testEachReturnsCorrectModels()
    {
        $this->seedData();
        $this->seedDataExtended();
        $position = HasOneThroughTestPosition::find(1);

        $position->contract()->each(function ($contract) {
            $this->assertEquals([
                'id',
                'user_id',
                'title',
                'body',
                'email',
                'created_at',
                'updated_at',
                'laravel_through_key', ], array_keys($contract->getAttributes()));
        });
    }

    public function testLazyReturnsCorrectModels()
    {
        $this->seedData();
        $this->seedDataExtended();
        $position = HasOneThroughTestPosition::find(1);

        $position->contract()->lazy()->each(function ($contract) {
            $this->assertEquals([
                'id',
                'user_id',
                'title',
                'body',
                'email',
                'created_at',
                'updated_at',
                'laravel_through_key', ], array_keys($contract->getAttributes()));
        });
    }

    public function testIntermediateSoftDeletesAreIgnored()
    {
        $this->seedData();
        HasOneThroughSoftDeletesTestUser::first()->delete();

        $contract = HasOneThroughSoftDeletesTestPosition::first()->contract;

        $this->assertSame('A title', $contract->title);
    }

    public function testEagerLoadingLoadsRelatedModelsCorrectly()
    {
        $this->seedData();
        $position = HasOneThroughSoftDeletesTestPosition::with('contract')->first();

        $this->assertSame('ps', $position->shortname);
        $this->assertSame('A title', $position->contract->title);
    }

    /**
     * Helpers...
     */
    protected function seedData()
    {
        HasOneThroughTestPosition::create(['id' => 1, 'name' => 'President', 'shortname' => 'ps'])
            ->user()->create(['id' => 1, 'email' => 'taylorotwell@gmail.com', 'position_short' => 'ps'])
            ->contract()->create(['title' => 'A title', 'body' => 'A body', 'email' => 'taylorotwell@gmail.com']);
    }

    protected function seedDataExtended()
    {
        $position = HasOneThroughTestPosition::create(['id' => 2, 'name' => 'Vice President', 'shortname' => 'vp']);
        $position->user()->create(['id' => 2, 'email' => 'example1@gmail.com', 'position_short' => 'vp'])
            ->contract()->create(
                ['title' => 'Example1 title1', 'body' => 'Example1 body1', 'email' => 'example1contract1@gmail.com']
            );
    }

    /**
     * Seed data for a default HasOneThrough setup.
     */
    protected function seedDefaultData()
    {
        HasOneThroughDefaultTestPosition::create(['id' => 1, 'name' => 'President'])
            ->user()->create(['id' => 1, 'email' => 'taylorotwell@gmail.com'])
            ->contract()->create(['title' => 'A title', 'body' => 'A body']);
    }

    /**
     * Drop the default tables.
     */
    protected function resetDefault()
    {
        $this->schema()->drop('users_default');
        $this->schema()->drop('contracts_default');
        $this->schema()->drop('positions_default');
    }

    /**
     * Migrate tables for classes with a Laravel "default" HasOneThrough setup.
     */
    protected function migrateDefault()
    {
        $this->schema()->create('users_default', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->unsignedInteger('has_one_through_default_test_position_id')->unique()->nullable();
            $table->timestamps();
        });

        $this->schema()->create('contracts_default', function ($table) {
            $table->increments('id');
            $table->integer('has_one_through_default_test_user_id')->unique();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        $this->schema()->create('positions_default', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
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
class HasOneThroughTestUser extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOne(HasOneThroughTestContract::class, 'user_id');
    }
}

/**
 * Eloquent Models...
 */
class HasOneThroughTestContract extends Eloquent
{
    protected $table = 'contracts';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasOneThroughTestUser::class, 'user_id');
    }
}

class HasOneThroughTestPosition extends Eloquent
{
    protected $table = 'positions';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOneThrough(HasOneThroughTestContract::class, HasOneThroughTestUser::class, 'position_id', 'user_id');
    }

    public function user()
    {
        return $this->hasOne(HasOneThroughTestUser::class, 'position_id');
    }
}

/**
 * Eloquent Models...
 */
class HasOneThroughDefaultTestUser extends Eloquent
{
    protected $table = 'users_default';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOne(HasOneThroughDefaultTestContract::class);
    }
}

/**
 * Eloquent Models...
 */
class HasOneThroughDefaultTestContract extends Eloquent
{
    protected $table = 'contracts_default';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasOneThroughDefaultTestUser::class);
    }
}

class HasOneThroughDefaultTestPosition extends Eloquent
{
    protected $table = 'positions_default';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOneThrough(HasOneThroughDefaultTestContract::class, HasOneThroughDefaultTestUser::class);
    }

    public function user()
    {
        return $this->hasOne(HasOneThroughDefaultTestUser::class);
    }
}

class HasOneThroughIntermediateTestPosition extends Eloquent
{
    protected $table = 'positions';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOneThrough(HasOneThroughTestContract::class, HasOneThroughTestUser::class, 'position_short', 'email', 'shortname', 'email');
    }

    public function user()
    {
        return $this->hasOne(HasOneThroughTestUser::class, 'position_id');
    }
}

class HasOneThroughSoftDeletesTestUser extends Eloquent
{
    use SoftDeletes;

    protected $table = 'users';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOne(HasOneThroughSoftDeletesTestContract::class, 'user_id');
    }
}

/**
 * Eloquent Models...
 */
class HasOneThroughSoftDeletesTestContract extends Eloquent
{
    protected $table = 'contracts';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasOneThroughSoftDeletesTestUser::class, 'user_id');
    }
}

class HasOneThroughSoftDeletesTestPosition extends Eloquent
{
    protected $table = 'positions';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOneThrough(HasOneThroughSoftDeletesTestContract::class, HasOneThroughTestUser::class, 'position_id', 'user_id');
    }

    public function user()
    {
        return $this->hasOne(HasOneThroughSoftDeletesTestUser::class, 'position_id');
    }
}
