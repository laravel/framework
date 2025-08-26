<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Builder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasOneThroughOfManyTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;
        $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    public function createSchema(): void
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
        });

        $this->schema()->create('intermediates', function ($table) {
            $table->increments('id');
            $table->foreignId('user_id');
        });

        $this->schema()->create('logins', function ($table) {
            $table->increments('id');
            $table->foreignId('intermediate_id');
            $table->dateTime('deleted_at')->nullable();
        });

        $this->schema()->create('states', function ($table) {
            $table->increments('id');
            $table->string('state');
            $table->string('type');
            $table->foreignId('intermediate_id');
            $table->timestamps();
        });

        $this->schema()->create('prices', function ($table) {
            $table->increments('id');
            $table->dateTime('published_at');
            $table->foreignId('intermediate_id');
        });
    }

    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('intermediates');
        $this->schema()->drop('logins');
        $this->schema()->drop('states');
        $this->schema()->drop('prices');
    }

    public function testItGuessesRelationName(): void
    {
        $user = HasOneThroughOfManyTestUser::make();
        $this->assertSame('latest_login', $user->latest_login()->getRelationName());
    }

    public function testItGuessesRelationNameAndAddsOfManyWhenTableNameIsRelationName(): void
    {
        $model = HasOneThroughOfManyTestModel::make();
        $this->assertSame('logins_of_many', $model->logins()->getRelationName());
    }

    public function testRelationNameCanBeSet(): void
    {
        $user = HasOneThroughOfManyTestUser::create();

        $relation = $user->latest_login()->ofMany('id', 'max', 'foo');
        $this->assertSame('foo', $relation->getRelationName());

        $relation = $user->latest_login()->latestOfMany('id', 'bar');
        $this->assertSame('bar', $relation->getRelationName());

        $relation = $user->latest_login()->oldestOfMany('id', 'baz');
        $this->assertSame('baz', $relation->getRelationName());
    }

    public function testCorrectLatestOfManyQuery(): void
    {
        $user = HasOneThroughOfManyTestUser::create();
        $relation = $user->latest_login();
        $this->assertSame('select "logins".* from "logins" inner join "intermediates" on "intermediates"."id" = "logins"."intermediate_id" inner join (select MAX("logins"."id") as "id_aggregate", "intermediates"."user_id" from "logins" inner join "intermediates" on "intermediates"."id" = "logins"."intermediate_id" where "intermediates"."user_id" = ? group by "intermediates"."user_id") as "latest_login" on "latest_login"."id_aggregate" = "logins"."id" and "latest_login"."user_id" = "intermediates"."user_id" where "intermediates"."user_id" = ?', $relation->getQuery()->toSql());
    }

    public function testEagerLoadingAppliesConstraintsToInnerJoinSubQuery(): void
    {
        $user = HasOneThroughOfManyTestUser::create();
        $relation = $user->latest_login();
        $relation->addEagerConstraints([$user]);
        $this->assertSame('select MAX("logins"."id") as "id_aggregate", "intermediates"."user_id" from "logins" inner join "intermediates" on "intermediates"."id" = "logins"."intermediate_id" where "intermediates"."user_id" = ? and "intermediates"."user_id" in (1) group by "intermediates"."user_id"', $relation->getOneOfManySubQuery()->toSql());
    }

    public function testEagerLoadingAppliesConstraintsToQuery(): void
    {
        $user = HasOneThroughOfManyTestUser::create();
        $relation = $user->latest_login();
        $relation->addEagerConstraints([$user]);
        $this->assertSame('select "logins".* from "logins" inner join "intermediates" on "intermediates"."id" = "logins"."intermediate_id" inner join (select MAX("logins"."id") as "id_aggregate", "intermediates"."user_id" from "logins" inner join "intermediates" on "intermediates"."id" = "logins"."intermediate_id" where "intermediates"."user_id" = ? and "intermediates"."user_id" in (1) group by "intermediates"."user_id") as "latest_login" on "latest_login"."id_aggregate" = "logins"."id" and "latest_login"."user_id" = "intermediates"."user_id" where "intermediates"."user_id" = ?', $relation->getQuery()->toSql());
    }

    public function testGlobalScopeIsNotAppliedWhenRelationIsDefinedWithoutGlobalScope(): void
    {
        HasOneThroughOfManyTestLogin::addGlobalScope('test', function ($query) {
            $query->orderBy($query->qualifyColumn('id'));
        });

        $user = HasOneThroughOfManyTestUser::create();
        $relation = $user->latest_login_without_global_scope();
        $relation->addEagerConstraints([$user]);
        $this->assertSame('select "logins".* from "logins" inner join "intermediates" on "intermediates"."id" = "logins"."intermediate_id" inner join (select MAX("logins"."id") as "id_aggregate", "intermediates"."user_id" from "logins" inner join "intermediates" on "intermediates"."id" = "logins"."intermediate_id" where "intermediates"."user_id" = ? and "intermediates"."user_id" in (1) group by "intermediates"."user_id") as "latestOfMany" on "latestOfMany"."id_aggregate" = "logins"."id" and "latestOfMany"."user_id" = "intermediates"."user_id" where "intermediates"."user_id" = ?', $relation->getQuery()->toSql());

        HasOneThroughOfManyTestLogin::addGlobalScope('test', function ($query) {
        });
    }

    public function testGlobalScopeIsNotAppliedWhenRelationIsDefinedWithoutGlobalScopeWithComplexQuery(): void
    {
        HasOneThroughOfManyTestPrice::addGlobalScope('test', function ($query) {
            $query->orderBy($query->qualifyColumn('id'));
        });

        $user = HasOneThroughOfManyTestUser::create();
        $relation = $user->price_without_global_scope();
        $this->assertSame('select "prices".* from "prices" inner join "intermediates" on "intermediates"."id" = "prices"."intermediate_id" inner join (select max("prices"."id") as "id_aggregate", min("prices"."published_at") as "published_at_aggregate", "intermediates"."user_id" from "prices" inner join "intermediates" on "intermediates"."id" = "prices"."intermediate_id" inner join (select max("prices"."published_at") as "published_at_aggregate", "intermediates"."user_id" from "prices" inner join "intermediates" on "intermediates"."id" = "prices"."intermediate_id" where "published_at" < ? and "intermediates"."user_id" = ? group by "intermediates"."user_id") as "price_without_global_scope" on "price_without_global_scope"."published_at_aggregate" = "prices"."published_at" and "price_without_global_scope"."user_id" = "intermediates"."user_id" where "published_at" < ? group by "intermediates"."user_id") as "price_without_global_scope" on "price_without_global_scope"."id_aggregate" = "prices"."id" and "price_without_global_scope"."published_at_aggregate" = "prices"."published_at" and "price_without_global_scope"."user_id" = "intermediates"."user_id" where "intermediates"."user_id" = ?', $relation->getQuery()->toSql());

        HasOneThroughOfManyTestPrice::addGlobalScope('test', function ($query) {
        });
    }

    public function testQualifyingSubSelectColumn(): void
    {
        $user = HasOneThroughOfManyTestUser::make();
        $this->assertSame('latest_login.id', $user->latest_login()->qualifySubSelectColumn('id'));
    }

    public function testItFailsWhenUsingInvalidAggregate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid aggregate [count] used within ofMany relation. Available aggregates: MIN, MAX');
        $user = HasOneThroughOfManyTestUser::make();
        $user->latest_login_with_invalid_aggregate();
    }

    public function testItGetsCorrectResults(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $previousLogin = $user->intermediates->last()->logins()->create();
        $latestLogin = $user->intermediates->first()->logins()->create();

        $result = $user->latest_login()->getResults();
        $this->assertNotNull($result);
        $this->assertSame($latestLogin->id, $result->id);
    }

    public function testResultDoesNotHaveAggregateColumn(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(1)->create();
        $user->intermediates->first()->logins()->create();

        $result = $user->latest_login()->getResults();
        $this->assertNotNull($result);
        $this->assertFalse(isset($result->id_aggregate));
    }

    public function testItGetsCorrectResultsUsingShortcutMethod(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $previousLogin = $user->intermediates->last()->logins()->create();
        $latestLogin = $user->intermediates->first()->logins()->create();

        $result = $user->latest_login_with_shortcut()->getResults();
        $this->assertNotNull($result);
        $this->assertSame($latestLogin->id, $result->id);
    }

    public function testItGetsCorrectResultsUsingShortcutReceivingMultipleColumnsMethod(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $user->intermediates->last()->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $price = $user->intermediates->first()->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);

        $result = $user->price_with_shortcut()->getResults();
        $this->assertNotNull($result);
        $this->assertSame($price->id, $result->id);
    }

    public function testKeyIsAddedToAggregatesWhenMissing(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $user->intermediates->last()->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $price = $user->intermediates->first()->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);

        $result = $user->price_without_key_in_aggregates()->getResults();
        $this->assertNotNull($result);
        $this->assertSame($price->id, $result->id);
    }

    public function testItGetsWithConstraintsCorrectResults(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $previousLogin = $user->intermediates->last()->logins()->create();
        $user->intermediates->first()->logins()->create();

        $result = $user->latest_login()->whereKey($previousLogin->getKey())->getResults();
        $this->assertNull($result);
    }

    public function testItEagerLoadsCorrectModels(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $user->intermediates->last()->logins()->create();
        $latestLogin = $user->intermediates->first()->logins()->create();

        $user = HasOneThroughOfManyTestUser::with('latest_login')->first();

        $this->assertTrue($user->relationLoaded('latest_login'));
        $this->assertSame($latestLogin->id, $user->latest_login->id);
    }

    public function testItJoinsOtherTableInSubQuery(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $user->intermediates->first()->logins()->create();

        $this->assertNull($user->latest_login_with_foo_state);

        $user->unsetRelation('latest_login_with_foo_state');
        $user->intermediates->first()->states()->create([
            'type' => 'foo',
            'state' => 'draft',
        ]);

        $this->assertNotNull($user->latest_login_with_foo_state);
    }

    public function testHasNested(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $previousLogin = $user->intermediates->first()->logins()->create();
        $latestLogin = $user->intermediates->last()->logins()->create();

        $found = HasOneThroughOfManyTestUser::whereHas('latest_login', function ($query) use ($latestLogin) {
            $query->where('logins.id', $latestLogin->id);
        })->exists();
        $this->assertTrue($found);

        $found = HasOneThroughOfManyTestUser::whereHas('latest_login', function ($query) use ($previousLogin) {
            $query->where('logins.id', $previousLogin->id);
        })->exists();
        $this->assertFalse($found);
    }

    public function testWithHasNested(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $previousLogin = $user->intermediates->first()->logins()->create();
        $latestLogin = $user->intermediates->last()->logins()->create();

        $found = HasOneThroughOfManyTestUser::withWhereHas('latest_login', function ($query) use ($latestLogin) {
            $query->where('logins.id', $latestLogin->id);
        })->first();

        $this->assertTrue((bool) $found);
        $this->assertTrue($found->relationLoaded('latest_login'));
        $this->assertEquals($found->latest_login->id, $latestLogin->id);

        $found = HasOneThroughOfManyTestUser::withWhereHas('latest_login', function ($query) use ($previousLogin) {
            $query->where('logins.id', $previousLogin->id);
        })->exists();

        $this->assertFalse($found);
    }

    public function testHasCount(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $user->intermediates->last()->logins()->create();
        $user->intermediates->first()->logins()->create();

        $user = HasOneThroughOfManyTestUser::withCount('latest_login')->first();
        $this->assertEquals(1, $user->latest_login_count);
    }

    public function testExists(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $previousLogin = $user->intermediates->last()->logins()->create();
        $latestLogin = $user->intermediates->first()->logins()->create();

        $this->assertFalse($user->latest_login()->whereKey($previousLogin->getKey())->exists());
        $this->assertTrue($user->latest_login()->whereKey($latestLogin->getKey())->exists());
    }

    public function testIsMethod(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $login1 = $user->intermediates->last()->logins()->create();
        $login2 = $user->intermediates->first()->logins()->create();

        $this->assertFalse($user->latest_login()->is($login1));
        $this->assertTrue($user->latest_login()->is($login2));
    }

    public function testIsNotMethod(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $login1 = $user->intermediates->last()->logins()->create();
        $login2 = $user->intermediates->first()->logins()->create();

        $this->assertTrue($user->latest_login()->isNot($login1));
        $this->assertFalse($user->latest_login()->isNot($login2));
    }

    public function testGet(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $previousLogin = $user->intermediates->last()->logins()->create();
        $latestLogin = $user->intermediates->first()->logins()->create();

        $latestLogins = $user->latest_login()->get();
        $this->assertCount(1, $latestLogins);
        $this->assertSame($latestLogin->id, $latestLogins->first()->id);

        $latestLogins = $user->latest_login()->whereKey($previousLogin->getKey())->get();
        $this->assertCount(0, $latestLogins);
    }

    public function testCount(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $user->intermediates->last()->logins()->create();
        $user->intermediates->first()->logins()->create();

        $this->assertSame(1, $user->latest_login()->count());
    }

    public function testAggregate(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $firstLogin = $user->intermediates->first()->logins()->create();
        $user->intermediates->last()->logins()->create();

        $user = HasOneThroughOfManyTestUser::first();
        $this->assertSame($firstLogin->id, $user->first_login->id);
    }

    public function testJoinConstraints(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $user->intermediates->last()->states()->create([
            'type' => 'foo',
            'state' => 'draft',
        ]);
        $currentForState = $user->intermediates->first()->states()->create([
            'type' => 'foo',
            'state' => 'active',
        ]);
        $user->intermediates->first()->states()->create([
            'type' => 'bar',
            'state' => 'baz',
        ]);

        $user = HasOneThroughOfManyTestUser::first();
        $this->assertSame($currentForState->id, $user->foo_state->id);
    }

    public function testMultipleAggregates(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $user->intermediates->last()->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $price = $user->intermediates->first()->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);

        $user = HasOneThroughOfManyTestUser::first();
        $this->assertSame($price->id, $user->price->id);
    }

    public function testEagerLoadingWithMultipleAggregates(): void
    {
        $user1 = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();
        $user2 = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();

        $user1->intermediates->last()->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $user1Price = $user1->intermediates->first()->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $user1->intermediates->first()->prices()->create([
            'published_at' => '2021-04-01 00:00:00',
        ]);

        $user2Price = $user2->intermediates->last()->prices()->create([
            'published_at' => '2021-05-01 00:00:00',
        ]);
        $user2->intermediates->first()->prices()->create([
            'published_at' => '2021-04-01 00:00:00',
        ]);

        $users = HasOneThroughOfManyTestUser::with('price')->get();

        $this->assertNotNull($users[0]->price);
        $this->assertSame($user1Price->id, $users[0]->price->id);

        $this->assertNotNull($users[1]->price);
        $this->assertSame($user2Price->id, $users[1]->price->id);
    }

    public function testWithExists(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(1)->create();

        $user = HasOneThroughOfManyTestUser::withExists('latest_login')->first();
        $this->assertFalse($user->latest_login_exists);

        $user->intermediates->first()->logins()->create();
        $user = HasOneThroughOfManyTestUser::withExists('latest_login')->first();
        $this->assertTrue($user->latest_login_exists);
    }

    public function testWithExistsWithConstraintsInJoinSubSelect(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(1)->create();
        $user = HasOneThroughOfManyTestUser::withExists('foo_state')->first();

        $this->assertFalse($user->foo_state_exists);

        $user->intermediates->first()->states()->create([
            'type' => 'foo',
            'state' => 'bar',
        ]);
        $user = HasOneThroughOfManyTestUser::withExists('foo_state')->first();
        $this->assertTrue($user->foo_state_exists);
    }

    public function testWithSoftDeletes(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(1)->create();
        $user->intermediates->first()->logins()->create();
        $user->latest_login_with_soft_deletes;
        $this->assertNotNull($user->latest_login_with_soft_deletes);
    }

    public function testWithConstraintNotInAggregate(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();

        $previousFoo = $user->intermediates->last()->states()->create([
            'type' => 'foo',
            'state' => 'bar',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
        $newFoo = $user->intermediates->first()->states()->create([
            'type' => 'foo',
            'state' => 'active',
            'updated_at' => '2021-01-01 12:00:00',
        ]);
        $newBar = $user->intermediates->first()->states()->create([
            'type' => 'bar',
            'state' => 'active',
            'updated_at' => '2021-01-01 12:00:00',
        ]);

        $this->assertSame($newFoo->id, $user->last_updated_foo_state->id);
    }

    public function testItGetsCorrectResultUsingAtLeastTwoAggregatesDistinctFromId(): void
    {
        $user = HasOneThroughOfManyTestUser::factory()->hasIntermediates(2)->create();

        $expectedState = $user->intermediates->last()->states()->create([
            'state' => 'state',
            'type' => 'type',
            'created_at' => '2023-01-01',
            'updated_at' => '2023-01-03',
        ]);

        $user->intermediates->first()->states()->create([
            'state' => 'state',
            'type' => 'type',
            'created_at' => '2023-01-01',
            'updated_at' => '2023-01-02',
        ]);

        $this->assertSame($user->latest_updated_latest_created_state->id, $expectedState->id);
    }

    protected function connection(): Connection
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    protected function schema(): Builder
    {
        return $this->connection()->getSchemaBuilder();
    }
}

class HasOneThroughOfManyTestUser extends Eloquent
{
    use HasFactory;
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
    protected static string $factory = HasOneThroughOfManyTestUserFactory::class;

    public function intermediates(): HasMany
    {
        return $this->hasMany(HasOneThroughOfManyTestIntermediate::class, 'user_id');
    }

    public function logins(): HasManyThrough
    {
        return $this->through('intermediates')->has('logins');
    }

    public function latest_login(): HasOneThrough
    {
        return $this->hasOneThrough(
            HasOneThroughOfManyTestLogin::class,
            HasOneThroughOfManyTestIntermediate::class,
            'user_id',
            'intermediate_id'
        )->ofMany();
    }

    public function latest_login_with_soft_deletes(): HasOneThrough
    {
        return $this->hasOneThrough(
            HasOneThroughOfManyTestLoginWithSoftDeletes::class,
            HasOneThroughOfManyTestIntermediate::class,
            'user_id',
            'intermediate_id',
        )->ofMany();
    }

    public function latest_login_with_shortcut(): HasOneThrough
    {
        return $this->logins()->one()->latestOfMany();
    }

    public function latest_login_with_invalid_aggregate(): HasOneThrough
    {
        return $this->logins()->one()->ofMany('id', 'count');
    }

    public function latest_login_without_global_scope(): HasOneThrough
    {
        return $this->logins()->one()->withoutGlobalScopes()->latestOfMany();
    }

    public function first_login(): HasOneThrough
    {
        return $this->logins()->one()->ofMany('id', 'min');
    }

    public function latest_login_with_foo_state(): HasOneThrough
    {
        return $this->logins()->one()->ofMany(
            ['id' => 'max'],
            function ($query) {
                $query->join('states', 'states.intermediate_id', 'logins.intermediate_id')
                    ->where('states.type', 'foo');
            }
        );
    }

    public function states(): HasManyThrough
    {
        return $this->through($this->intermediates())
            ->has(fn ($intermediate) => $intermediate->states());
    }

    public function foo_state(): HasOneThrough
    {
        return $this->states()->one()->ofMany(
            ['id' => 'max'],
            function ($q) {
                $q->where('type', 'foo');
            }
        );
    }

    public function last_updated_foo_state(): HasOneThrough
    {
        return $this->states()->one()->ofMany([
            'updated_at' => 'max',
            'id' => 'max',
        ], function ($q) {
            $q->where('type', 'foo');
        });
    }

    public function prices(): HasManyThrough
    {
        return $this->throughIntermediates()->hasPrices();
    }

    public function price(): HasOneThrough
    {
        return $this->prices()->one()->ofMany([
            'published_at' => 'max',
            'id' => 'max',
        ], function ($q) {
            $q->where('published_at', '<', now());
        });
    }

    public function price_without_key_in_aggregates(): HasOneThrough
    {
        return $this->prices()->one()->ofMany(['published_at' => 'MAX']);
    }

    public function price_with_shortcut(): HasOneThrough
    {
        return $this->prices()->one()->latestOfMany(['published_at', 'id']);
    }

    public function price_without_global_scope(): HasOneThrough
    {
        return $this->prices()->one()->withoutGlobalScopes()->ofMany([
            'published_at' => 'max',
            'id' => 'max',
        ], function ($q) {
            $q->where('published_at', '<', now());
        });
    }

    public function latest_updated_latest_created_state(): HasOneThrough
    {
        return $this->states()->one()->ofMany([
            'updated_at' => 'max',
            'created_at' => 'max',
        ]);
    }
}

class HasOneThroughOfManyTestIntermediate extends Eloquent
{
    use HasFactory;
    protected $table = 'intermediates';
    protected $guarded = [];
    public $timestamps = false;
    protected static string $factory = HasOneThroughOfManyTestIntermediateFactory::class;

    public function logins(): HasMany
    {
        return $this->hasMany(HasOneThroughOfManyTestLogin::class, 'intermediate_id');
    }

    public function states(): HasMany
    {
        return $this->hasMany(HasOneThroughOfManyTestState::class, 'intermediate_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(HasOneThroughOfManyTestPrice::class, 'intermediate_id');
    }
}

class HasOneThroughOfManyTestModel extends Eloquent
{
    public function logins(): HasOneThrough
    {
        return $this->hasOneThrough(
            HasOneThroughOfManyTestLogin::class,
            HasOneThroughOfManyTestIntermediate::class,
            'user_id',
            'intermediate_id',
        )->ofMany();
    }
}

class HasOneThroughOfManyTestLogin extends Eloquent
{
    protected $table = 'logins';
    protected $guarded = [];
    public $timestamps = false;
}

class HasOneThroughOfManyTestLoginWithSoftDeletes extends Eloquent
{
    use SoftDeletes;

    protected $table = 'logins';
    protected $guarded = [];
    public $timestamps = false;
}

class HasOneThroughOfManyTestState extends Eloquent
{
    protected $table = 'states';
    protected $guarded = [];
    public $timestamps = true;
    protected $fillable = ['type', 'state', 'updated_at'];
}

class HasOneThroughOfManyTestPrice extends Eloquent
{
    protected $table = 'prices';
    protected $guarded = [];
    public $timestamps = false;
    protected $fillable = ['published_at'];
    protected $casts = ['published_at' => 'datetime'];
}

class HasOneThroughOfManyTestUserFactory extends Factory
{
    protected $model = HasOneThroughOfManyTestUser::class;

    public function definition(): array
    {
        return [];
    }
}

class HasOneThroughOfManyTestIntermediateFactory extends Factory
{
    protected $model = HasOneThroughOfManyTestIntermediate::class;

    public function definition(): array
    {
        return ['user_id' => HasOneThroughOfManyTestUser::factory()];
    }
}
