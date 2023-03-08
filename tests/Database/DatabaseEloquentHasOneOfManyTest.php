<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasOneOfManyTest extends TestCase
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

    public function testItGuessesRelationName()
    {
        $user = HasOneOfManyTestUser::make();
        $this->assertSame('latest_login', $user->latest_login()->getRelationName());
    }

    public function testItGuessesRelationNameAndAddsOfManyWhenTableNameIsRelationName()
    {
        $model = HasOneOfManyTestModel::make();
        $this->assertSame('logins_of_many', $model->logins()->getRelationName());
    }

    public function testRelationNameCanBeSet()
    {
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

    public function testEagerLoadingAppliesConstraintsToInnerJoinSubQuery()
    {
        $user = HasOneOfManyTestUser::create();
        $relation = $user->latest_login();
        $relation->addEagerConstraints([$user]);
        $this->assertSame('select MAX("logins"."id") as "id_aggregate", "logins"."user_id" from "logins" where "logins"."user_id" = ? and "logins"."user_id" is not null and "logins"."user_id" in (1) group by "logins"."user_id"', $relation->getOneOfManySubQuery()->toSql());
    }

    public function testGlobalScopeIsNotAppliedWhenRelationIsDefinedWithoutGlobalScope()
    {
        HasOneOfManyTestLogin::addGlobalScope('test', function ($query) {
            $query->orderBy('id');
        });

        $user = HasOneOfManyTestUser::create();
        $relation = $user->latest_login_without_global_scope();
        $relation->addEagerConstraints([$user]);
        $this->assertSame('select "logins".* from "logins" inner join (select MAX("logins"."id") as "id_aggregate", "logins"."user_id" from "logins" where "logins"."user_id" = ? and "logins"."user_id" is not null and "logins"."user_id" in (1) group by "logins"."user_id") as "latestOfMany" on "latestOfMany"."id_aggregate" = "logins"."id" and "latestOfMany"."user_id" = "logins"."user_id" where "logins"."user_id" = ? and "logins"."user_id" is not null', $relation->getQuery()->toSql());

        HasOneOfManyTestLogin::addGlobalScope('test', function ($query) {
        });
    }

    public function testGlobalScopeIsNotAppliedWhenRelationIsDefinedWithoutGlobalScopeWithComplexQuery()
    {
        HasOneOfManyTestPrice::addGlobalScope('test', function ($query) {
            $query->orderBy('id');
        });

        $user = HasOneOfManyTestUser::create();
        $relation = $user->price_without_global_scope();
        $this->assertSame('select "prices".* from "prices" inner join (select max("prices"."id") as "id_aggregate", "prices"."user_id" from "prices" inner join (select max("prices"."published_at") as "published_at_aggregate", "prices"."user_id" from "prices" where "published_at" < ? and "prices"."user_id" = ? and "prices"."user_id" is not null group by "prices"."user_id") as "price_without_global_scope" on "price_without_global_scope"."published_at_aggregate" = "prices"."published_at" and "price_without_global_scope"."user_id" = "prices"."user_id" where "published_at" < ? group by "prices"."user_id") as "price_without_global_scope" on "price_without_global_scope"."id_aggregate" = "prices"."id" and "price_without_global_scope"."user_id" = "prices"."user_id" where "prices"."user_id" = ? and "prices"."user_id" is not null', $relation->getQuery()->toSql());

        HasOneOfManyTestPrice::addGlobalScope('test', function ($query) {
        });
    }

    public function testQualifyingSubSelectColumn()
    {
        $user = HasOneOfManyTestUser::create();
        $this->assertSame('latest_login.id', $user->latest_login()->qualifySubSelectColumn('id'));
    }

    public function testItFailsWhenUsingInvalidAggregate()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid aggregate [count] used within ofMany relation. Available aggregates: MIN, MAX');
        $user = HasOneOfManyTestUser::make();
        $user->latest_login_with_invalid_aggregate();
    }

    public function testItGetsCorrectResults()
    {
        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $result = $user->latest_login()->getResults();
        $this->assertNotNull($result);
        $this->assertSame($latestLogin->id, $result->id);
    }

    public function testResultDoesNotHaveAggregateColumn()
    {
        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();

        $result = $user->latest_login()->getResults();
        $this->assertNotNull($result);
        $this->assertFalse(isset($result->id_aggregate));
    }

    public function testItGetsCorrectResultsUsingShortcutMethod()
    {
        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $result = $user->latest_login_with_shortcut()->getResults();
        $this->assertNotNull($result);
        $this->assertSame($latestLogin->id, $result->id);
    }

    public function testItGetsCorrectResultsUsingShortcutReceivingMultipleColumnsMethod()
    {
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

    public function testKeyIsAddedToAggregatesWhenMissing()
    {
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

    public function testItGetsWithConstraintsCorrectResults()
    {
        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $user->logins()->create();

        $result = $user->latest_login()->whereKey($previousLogin->getKey())->getResults();
        $this->assertNull($result);
    }

    public function testItEagerLoadsCorrectModels()
    {
        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $user = HasOneOfManyTestUser::with('latest_login')->first();

        $this->assertTrue($user->relationLoaded('latest_login'));
        $this->assertSame($latestLogin->id, $user->latest_login->id);
    }

    public function testItJoinsOtherTableInSubQuery()
    {
        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();

        $this->assertNull($user->latest_login_with_foo_state);

        $user->unsetRelation('latest_login_with_foo_state');
        $user->states()->create([
            'type' => 'foo',
            'state' => 'draft',
        ]);

        $this->assertNotNull($user->latest_login_with_foo_state);
    }

    public function testHasNested()
    {
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

    public function testWithHasNested()
    {
        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $found = HasOneOfManyTestUser::withWhereHas('latest_login', function ($query) use ($latestLogin) {
            $query->where('logins.id', $latestLogin->id);
        })->first();

        $this->assertTrue((bool) $found);
        $this->assertTrue($found->relationLoaded('latest_login'));
        $this->assertEquals($found->latest_login->id, $latestLogin->id);

        $found = HasOneOfManyTestUser::withWhereHas('latest_login', function ($query) use ($previousLogin) {
            $query->where('logins.id', $previousLogin->id);
        })->exists();

        $this->assertFalse($found);
    }

    public function testHasCount()
    {
        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $user->logins()->create();

        $user = HasOneOfManyTestUser::withCount('latest_login')->first();
        $this->assertEquals(1, $user->latest_login_count);
    }

    public function testExists()
    {
        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $this->assertFalse($user->latest_login()->whereKey($previousLogin->getKey())->exists());
        $this->assertTrue($user->latest_login()->whereKey($latestLogin->getKey())->exists());
    }

    public function testIsMethod()
    {
        $user = HasOneOfManyTestUser::create();
        $login1 = $user->latest_login()->create();
        $login2 = $user->latest_login()->create();

        $this->assertFalse($user->latest_login()->is($login1));
        $this->assertTrue($user->latest_login()->is($login2));
    }

    public function testIsNotMethod()
    {
        $user = HasOneOfManyTestUser::create();
        $login1 = $user->latest_login()->create();
        $login2 = $user->latest_login()->create();

        $this->assertTrue($user->latest_login()->isNot($login1));
        $this->assertFalse($user->latest_login()->isNot($login2));
    }

    public function testGet()
    {
        $user = HasOneOfManyTestUser::create();
        $previousLogin = $user->logins()->create();
        $latestLogin = $user->logins()->create();

        $latestLogins = $user->latest_login()->get();
        $this->assertCount(1, $latestLogins);
        $this->assertSame($latestLogin->id, $latestLogins->first()->id);

        $latestLogins = $user->latest_login()->whereKey($previousLogin->getKey())->get();
        $this->assertCount(0, $latestLogins);
    }

    public function testCount()
    {
        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $user->logins()->create();

        $this->assertSame(1, $user->latest_login()->count());
    }

    public function testAggregate()
    {
        $user = HasOneOfManyTestUser::create();
        $firstLogin = $user->logins()->create();
        $user->logins()->create();

        $user = HasOneOfManyTestUser::first();
        $this->assertSame($firstLogin->id, $user->first_login->id);
    }

    public function testJoinConstraints()
    {
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

    public function testMultipleAggregates()
    {
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

    public function testEagerLoadingWithMultipleAggregates()
    {
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

    public function testWithExists()
    {
        $user = HasOneOfManyTestUser::create();

        $user = HasOneOfManyTestUser::withExists('latest_login')->first();
        $this->assertFalse($user->latest_login_exists);

        $user->logins()->create();
        $user = HasOneOfManyTestUser::withExists('latest_login')->first();
        $this->assertTrue($user->latest_login_exists);
    }

    public function testWithExistsWithConstraintsInJoinSubSelect()
    {
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

    public function testWithSoftDeletes()
    {
        $user = HasOneOfManyTestUser::create();
        $user->logins()->create();
        $user->latest_login_with_soft_deletes;
        $this->assertNotNull($user->latest_login_with_soft_deletes);
    }

    public function testWithContraintNotInAggregate()
    {
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

    public function latest_login_with_foo_state()
    {
        return $this->hasOne(HasOneOfManyTestLogin::class, 'user_id')->ofMany(
            ['id' => 'max'],
            function ($query) {
                $query->join('states', 'states.user_id', 'logins.user_id')
                    ->where('states.type', 'foo');
            }
        );
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
