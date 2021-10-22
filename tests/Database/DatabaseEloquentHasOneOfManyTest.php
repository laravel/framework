<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @group one-of-many
 */
class DatabaseEloquentHasOneOfManyTest extends TestCase
{


    protected function setUp(): void
    {
        //
    }

    protected function addConnection($connection)
    {
        $this->connection = $connection['driver'];
        $db = new DB;
        $db->addConnection($connection);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();

        return $db;
    }

    public function dbConnectionProvider() {
        return [[
            function() {
                $this->addConnection([
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                ]);
            }, 'sqlite'
        ], [
            function() {
                $db = $this->addConnection([
                    'driver' => 'mysql',
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'username' => 'root',
                    'database' => 'forge',
                    'password' => '',
                ]);

                $db->getConnection('default')->statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
            }, 'msyql'
        ]];
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
        });

        $this->schema()->create('logins', function ($table) {
            $table->increments('id');
            $table->foreignId('user_id');
            $table->dateTime('deleted_at')->nullable();
        });

        $this->schema()->create('states', function ($table) {
            $table->increments('id');
            $table->string('state');
            $table->string('type');
            $table->foreignId('user_id');
            $table->timestamps();
        });

        $this->schema()->create('prices', function ($table) {
            $table->increments('id');
            $table->dateTime('published_at');
            $table->foreignId('user_id');
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
        $this->schema()->drop('logins');
        $this->schema()->drop('states');
        $this->schema()->drop('prices');
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testItGuessesRelationName($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::make();
        $this->assertSame('latest_login', $user->latest_login()->getRelationName());
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testItGuessesRelationNameAndAddsOfManyWhenTableNameIsRelationName($setupConnection)
    {
        $setupConnection();

        $model = HasOneOfManyTestModel::make();
        $this->assertSame('logins_of_many', $model->logins()->getRelationName());
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testRelationNameCanBeSet($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();

        // Using "ofMany"
        $relation = $user->latest_login()->ofMany('id', 'max', 'foo');
        $this->assertSame('foo', $relation->getRelationName());

        // Using "latestOfMAny"
        $relation = $user->latest_login()->latestOfMAny('id', 'bar');
        $this->assertSame('bar', $relation->getRelationName());

        // Using "oldestOfMAny"
        $relation = $user->latest_login()->oldestOfMAny('id', 'baz');
        $this->assertSame('baz', $relation->getRelationName());
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testEagerLoadingAppliesConstraintsToInnerJoinSubQuery($setupConnection, $driver)
    {
        $setupConnection();

        if(! in_array($driver, ['sqlite', 'msyql'])){
            $this->markTestSkipped();
        }

        $user = HasOneOfManyTestUser::create();
        $relation = $user->latest_login();
        $relation->addEagerConstraints([$user]);

        if($driver == 'msyql') {
            $rawQuery = 'select MAX(`id`) as `id_aggregate`, `logins`.`user_id` from `logins` where `logins`.`user_id` = ? and `logins`.`user_id` is not null and `logins`.`user_id` in (1) group by `logins`.`user_id`';
        }

        if($driver == 'sqlite') {
            $rawQuery = 'select MAX("id") as "id_aggregate", "logins"."user_id" from "logins" where "logins"."user_id" = ? and "logins"."user_id" is not null and "logins"."user_id" in (1) group by "logins"."user_id"';
        }

        $this->assertSame($rawQuery, $relation->getOneOfManySubQuery()->toSql());
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testGlobalScopeIsNotAppliedWhenRelationIsDefinedWithoutGlobalScope($setupConnection, $driver)
    {
        $setupConnection();

        if(! in_array($driver, ['sqlite', 'msyql'])){
            $this->markTestSkipped();
        }

        HasOneOfManyTestLogin::addGlobalScope(function ($query) {
            $query->orderBy('id');
        });

        $user = HasOneOfManyTestUser::create();
        $relation = $user->latest_login_without_global_scope();
        $relation->addEagerConstraints([$user]);

        if($driver == 'msyql') {
            $rawQuery = 'select `logins`.* from `logins` inner join (select MAX(`id`) as `id_aggregate`, `logins`.`user_id` from `logins` where `logins`.`user_id` = ? and `logins`.`user_id` is not null and `logins`.`user_id` in (1) group by `logins`.`user_id`) as `latestOfMany` on `latestOfMany`.`id_aggregate` = `logins`.`id` and `latestOfMany`.`user_id` = `logins`.`user_id` where `logins`.`user_id` = ? and `logins`.`user_id` is not null';
        }

        if($driver == 'sqlite') {
            $rawQuery = 'select "logins".* from "logins" inner join (select MAX("id") as "id_aggregate", "logins"."user_id" from "logins" where "logins"."user_id" = ? and "logins"."user_id" is not null and "logins"."user_id" in (1) group by "logins"."user_id") as "latestOfMany" on "latestOfMany"."id_aggregate" = "logins"."id" and "latestOfMany"."user_id" = "logins"."user_id" where "logins"."user_id" = ? and "logins"."user_id" is not null';
        }

        $this->assertSame($rawQuery, $relation->getQuery()->toSql());
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testGlobalScopeIsNotAppliedWhenRelationIsDefinedWithoutGlobalScopeWithComplexQuery($setupConnection, $driver)
    {
        $setupConnection();

        if(! in_array($driver, ['sqlite', 'msyql'])){
            $this->markTestSkipped();
        }

        HasOneOfManyTestPrice::addGlobalScope(function ($query) {
            $query->orderBy('id');
        });

        $user = HasOneOfManyTestUser::create();
        $relation = $user->price_without_global_scope();

        if($driver == 'msyql') {
            $rawQuery = 'select `prices`.* from `prices` inner join (select max(`id`) as `id_aggregate`, `prices`.`user_id` from `prices` inner join (select max(`published_at`) as `published_at_aggregate`, `prices`.`user_id` from `prices` where `published_at` < ? and `prices`.`user_id` = ? and `prices`.`user_id` is not null group by `prices`.`user_id`) as `price_without_global_scope` on `price_without_global_scope`.`published_at_aggregate` = `prices`.`published_at` and `price_without_global_scope`.`user_id` = `prices`.`user_id` where `published_at` < ? group by `prices`.`user_id`) as `price_without_global_scope` on `price_without_global_scope`.`id_aggregate` = `prices`.`id` and `price_without_global_scope`.`user_id` = `prices`.`user_id` where `prices`.`user_id` = ? and `prices`.`user_id` is not null';
        }

        if($driver == 'sqlite') {
            $rawQuery = 'select "prices".* from "prices" inner join (select max("id") as "id_aggregate", "prices"."user_id" from "prices" inner join (select max("published_at") as "published_at_aggregate", "prices"."user_id" from "prices" where "published_at" < ? and "prices"."user_id" = ? and "prices"."user_id" is not null group by "prices"."user_id") as "price_without_global_scope" on "price_without_global_scope"."published_at_aggregate" = "prices"."published_at" and "price_without_global_scope"."user_id" = "prices"."user_id" where "published_at" < ? group by "prices"."user_id") as "price_without_global_scope" on "price_without_global_scope"."id_aggregate" = "prices"."id" and "price_without_global_scope"."user_id" = "prices"."user_id" where "prices"."user_id" = ? and "prices"."user_id" is not null';
        }

        $this->assertSame($rawQuery, $relation->getQuery()->toSql());
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testQualifyingSubSelectColumn($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $this->assertSame('latest_login.id', $user->latest_login()->qualifySubSelectColumn('id'));
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testItFailsWhenUsingInvalidAggregate($setupConnection)
    {
        $setupConnection();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid aggregate [count] used within ofMany relation. Available aggregates: MIN, MAX');
        $user = HasOneOfManyTestUser::make();
        $user->latest_login_with_invalid_aggregate();
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testItGetsCorrectResults($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $result = $user->latest_login()->getResults();
        $this->assertNotNull($result);
        $this->assertSame($latestLogin->id, $result->id);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testResultDoesNotHaveAggregateColumn($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();

        $result = $user->latest_login()->getResults();
        $this->assertNotNull($result);
        $this->assertFalse(isset($result->id_aggregate));
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testItGetsCorrectResultsUsingShortcutMethod($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $result = $user->latest_login_with_shortcut()->getResults();
        $this->assertNotNull($result);
        $this->assertSame($latestLogin->id, $result->id);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testItGetsCorrectResultsUsingShortcutReceivingMultipleColumnsMethod($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $user->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $price = $user->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);

        $result = $user->price_with_shortcut()->getResults();
        $this->assertNotNull($result);
        $this->assertSame($price->id, $result->id);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testKeyIsAddedToAggregatesWhenMissing($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $user->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $price = $user->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);

        $result = $user->price_without_key_in_aggregates()->getResults();
        $this->assertNotNull($result);
        $this->assertSame($price->id, $result->id);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testItGetsWithConstraintsCorrectResults($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $user->logins()->create();

        $result = $user->latest_login()->whereKey($previousLogin->getKey())->getResults();
        $this->assertNull($result);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testItEagerLoadsCorrectModels($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $user = HasOneOfManyTestUser::with('latest_login')->first();

        $this->assertTrue($user->relationLoaded('latest_login'));
        $this->assertSame($latestLogin->id, $user->latest_login->id);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testHasNested($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $found = HasOneOfManyTestUser::whereHas('latest_login', function ($query) use ($latestLogin) {
            $query->where('logins.id', $latestLogin->id);
        })->exists();
        $this->assertTrue($found);

        $found = HasOneOfManyTestUser::whereHas('latest_login', function ($query) use ($previousLogin) {
            $query->where('logins.id', $previousLogin->id);
        })->exists();
        $this->assertFalse($found);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testHasCount($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $user->logins()->create();

        $user = HasOneOfManyTestUser::withCount('latest_login')->first();
        $this->assertEquals(1, $user->latest_login_count);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testExists($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $this->assertFalse($user->latest_login()->whereKey($previousLogin->getKey())->exists());
        $this->assertTrue($user->latest_login()->whereKey($latestLogin->getKey())->exists());
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testIsMethod($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $login1 = $user->latest_login()->create();
        $login2 = $user->latest_login()->create();

        $this->assertFalse($user->latest_login()->is($login1));
        $this->assertTrue($user->latest_login()->is($login2));
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testIsNotMethod($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $login1 = $user->latest_login()->create();
        $login2 = $user->latest_login()->create();

        $this->assertTrue($user->latest_login()->isNot($login1));
        $this->assertFalse($user->latest_login()->isNot($login2));
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testGet($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $latestLogins = $user->latest_login()->get();
        $this->assertCount(1, $latestLogins);
        $this->assertSame($latestLogin->id, $latestLogins->first()->id);

        $latestLogins = $user->latest_login()->whereKey($previousLogin->getKey())->get();
        $this->assertCount(0, $latestLogins);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testCount($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $user->logins()->create();

        $this->assertSame(1, $user->latest_login()->count());
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testAggregate($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $firstLogin = $user->logins()->create();
        $user->logins()->create();

        $user = HasOneOfManyTestUser::first();
        $this->assertSame($firstLogin->id, $user->first_login->id);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testJoinConstraints($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $user->states()->create([
            'type' => 'foo',
            'state' => 'draft',
        ]);
        $currentForState = $user->states()->create([
            'type' => 'foo',
            'state' => 'active',
        ]);
        $user->states()->create([
            'type' => 'bar',
            'state' => 'baz',
        ]);

        $user = HasOneOfManyTestUser::first();
        $this->assertSame($currentForState->id, $user->foo_state->id);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testMultipleAggregates($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();

        $user->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $price = $user->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);

        $user = HasOneOfManyTestUser::first();
        $this->assertSame($price->id, $user->price->id);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testEagerLoadingWithMultipleAggregates($setupConnection)
    {
        $setupConnection();

        $user1 = HasOneOfManyTestUser::create();
        $user2 = HasOneOfManyTestUser::create();

        $user1->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $user1Price = $user1->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $user1->prices()->create([
            'published_at' => '2021-04-01 00:00:00',
        ]);

        $user2Price = $user2->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $user2->prices()->create([
            'published_at' => '2021-04-01 00:00:00',
        ]);

        $users = HasOneOfManyTestUser::with('price')->get();

        $this->assertNotNull($users[0]->price);
        $this->assertSame($user1Price->id, $users[0]->price->id);

        $this->assertNotNull($users[1]->price);
        $this->assertSame($user2Price->id, $users[1]->price->id);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testWithExists($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();

        $user = HasOneOfManyTestUser::withExists('latest_login')->first();
        $this->assertFalse($user->latest_login_exists);

        $user->logins()->create();
        $user = HasOneOfManyTestUser::withExists('latest_login')->first();
        $this->assertTrue($user->latest_login_exists);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testWithExistsWithConstraintsInJoinSubSelect($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();

        $user = HasOneOfManyTestUser::withExists('foo_state')->first();

        $this->assertFalse($user->foo_state_exists);

        $user->states()->create([
            'type' => 'foo',
            'state' => 'bar',
        ]);
        $user = HasOneOfManyTestUser::withExists('foo_state')->first();
        $this->assertTrue($user->foo_state_exists);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testWithSoftDeletes($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $user->latest_login_with_soft_deletes;
        $this->assertNotNull($user->latest_login_with_soft_deletes);
    }

    /**
     * @dataProvider dbConnectionProvider
     */
    public function testWithContraintNotInAggregate($setupConnection)
    {
        $setupConnection();

        $user = HasOneOfManyTestUser::create();

        $previousFoo = $user->states()->create([
            'type' => 'foo',
            'state' => 'bar',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
        $newFoo = $user->states()->create([
            'type' => 'foo',
            'state' => 'active',
            'updated_at' => '2021-01-01 12:00:00',
        ]);
        $newBar = $user->states()->create([
            'type' => 'bar',
            'state' => 'active',
            'updated_at' => '2021-01-01 12:00:00',
        ]);

        $this->assertSame($newFoo->id, $user->last_updated_foo_state->id);
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
class HasOneOfManyTestUser extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;

    public function logins()
    {
        return $this->hasMany(HasOneOfManyTestLogin::class, 'user_id');
    }

    public function latest_login()
    {
        return $this->hasOne(HasOneOfManyTestLogin::class, 'user_id')->ofMany();
    }

    public function latest_login_with_soft_deletes()
    {
        return $this->hasOne(HasOneOfManyTestLoginWithSoftDeletes::class, 'user_id')->ofMany();
    }

    public function latest_login_with_shortcut()
    {
        return $this->hasOne(HasOneOfManyTestLogin::class, 'user_id')->latestOfMany();
    }

    public function latest_login_with_invalid_aggregate()
    {
        return $this->hasOne(HasOneOfManyTestLogin::class, 'user_id')->ofMany('id', 'count');
    }

    public function latest_login_without_global_scope()
    {
        return $this->hasOne(HasOneOfManyTestLogin::class, 'user_id')->withoutGlobalScopes()->latestOfMany();
    }

    public function first_login()
    {
        return $this->hasOne(HasOneOfManyTestLogin::class, 'user_id')->ofMany('id', 'min');
    }

    public function states()
    {
        return $this->hasMany(HasOneOfManyTestState::class, 'user_id');
    }

    public function foo_state()
    {
        return $this->hasOne(HasOneOfManyTestState::class, 'user_id')->ofMany(
            ['id' => 'max'],
            function ($q) {
                $q->where('type', 'foo');
            }
        );
    }

    public function last_updated_foo_state()
    {
        return $this->hasOne(HasOneOfManyTestState::class, 'user_id')->ofMany([
            'updated_at' => 'max',
            'id' => 'max',
        ], function ($q) {
            $q->where('type', 'foo');
        });
    }

    public function prices()
    {
        return $this->hasMany(HasOneOfManyTestPrice::class, 'user_id');
    }

    public function price()
    {
        return $this->hasOne(HasOneOfManyTestPrice::class, 'user_id')->ofMany([
            'published_at' => 'max',
            'id' => 'max',
        ], function ($q) {
            $q->where('published_at', '<', now());
        });
    }

    public function price_without_key_in_aggregates()
    {
        return $this->hasOne(HasOneOfManyTestPrice::class, 'user_id')->ofMany(['published_at' => 'MAX']);
    }

    public function price_with_shortcut()
    {
        return $this->hasOne(HasOneOfManyTestPrice::class, 'user_id')->latestOfMany(['published_at', 'id']);
    }

    public function price_without_global_scope()
    {
        return $this->hasOne(HasOneOfManyTestPrice::class, 'user_id')->withoutGlobalScopes()->ofMany([
            'published_at' => 'max',
            'id' => 'max',
        ], function ($q) {
            $q->where('published_at', '<', now());
        });
    }
}

class HasOneOfManyTestModel extends Eloquent
{
    public function logins()
    {
        return $this->hasOne(HasOneOfManyTestLogin::class)->ofMany();
    }
}

class HasOneOfManyTestLogin extends Eloquent
{
    protected $table = 'logins';
    protected $guarded = [];
    public $timestamps = false;
}

class HasOneOfManyTestLoginWithSoftDeletes extends Eloquent
{
    use SoftDeletes;

    protected $table = 'logins';
    protected $guarded = [];
    public $timestamps = false;
}

class HasOneOfManyTestState extends Eloquent
{
    protected $table = 'states';
    protected $guarded = [];
    public $timestamps = true;
    protected $fillable = ['type', 'state', 'updated_at'];
}

class HasOneOfManyTestPrice extends Eloquent
{
    protected $table = 'prices';
    protected $guarded = [];
    public $timestamps = false;
    protected $fillable = ['published_at'];
    protected $casts = ['published_at' => 'datetime'];
}
