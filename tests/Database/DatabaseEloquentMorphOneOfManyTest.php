<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentMorphOneOfManyTest extends TestCase
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
        $this->schema()->create('products', function ($table) {
            $table->increments('id');
        });

        $this->schema()->create('states', function ($table) {
            $table->increments('id');
            $table->morphs('stateful');
            $table->string('state');
            $table->string('type')->nullable();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('products');
        $this->schema()->drop('states');
    }

    public function testEagerLoadingAppliesConstraintsToInnerJoinSubQuery()
    {
        $product = MorphOneOfManyTestProduct::create();
        $relation = $product->current_state();
        $relation->addEagerConstraints([$product]);
        $this->assertSame('select MAX("states"."id") as "id_aggregate", "states"."stateful_id", "states"."stateful_type" from "states" where "states"."stateful_type" = ? and "states"."stateful_id" = ? and "states"."stateful_id" is not null and "states"."stateful_id" in (1) and "states"."stateful_type" = ? group by "states"."stateful_id", "states"."stateful_type"', $relation->getOneOfManySubQuery()->toSql());
    }

    public function testReceivingModel()
    {
        $product = MorphOneOfManyTestProduct::create();
        $product->states()->create([
            'state' => 'draft',
        ]);
        $product->states()->create([
            'state' => 'active',
        ]);

        $this->assertNotNull($product->current_state);
        $this->assertSame('active', $product->current_state->state);
    }

    public function testMorphType()
    {
        $product = MorphOneOfManyTestProduct::create();
        $product->states()->create([
            'state' => 'draft',
        ]);
        $product->states()->create([
            'state' => 'active',
        ]);
        $state = $product->states()->make([
            'state' => 'foo',
        ]);
        $state->stateful_type = 'bar';
        $state->save();

        $this->assertNotNull($product->current_state);
        $this->assertSame('active', $product->current_state->state);
    }

    public function testExists()
    {
        $product = MorphOneOfManyTestProduct::create();
        $previousState = $product->states()->create([
            'state' => 'draft',
        ]);
        $currentState = $product->states()->create([
            'state' => 'active',
        ]);

        $exists = MorphOneOfManyTestProduct::whereHas('current_state', function ($q) use ($previousState) {
            $q->whereKey($previousState->getKey());
        })->exists();
        $this->assertFalse($exists);

        $exists = MorphOneOfManyTestProduct::whereHas('current_state', function ($q) use ($currentState) {
            $q->whereKey($currentState->getKey());
        })->exists();
        $this->assertTrue($exists);
    }

    public function testWithExists()
    {
        $product = MorphOneOfManyTestProduct::create();

        $product = MorphOneOfManyTestProduct::withExists('current_state')->first();
        $this->assertFalse($product->current_state_exists);

        $product->states()->create([
            'state' => 'draft',
        ]);
        $product = MorphOneOfManyTestProduct::withExists('current_state')->first();
        $this->assertTrue($product->current_state_exists);
    }

    public function testWithExistsWithConstraintsInJoinSubSelect()
    {
        $product = MorphOneOfManyTestProduct::create();

        $product = MorphOneOfManyTestProduct::withExists('current_foo_state')->first();
        $this->assertFalse($product->current_foo_state_exists);

        $product->states()->create([
            'state' => 'draft',
            'type' => 'foo',
        ]);
        $product = MorphOneOfManyTestProduct::withExists('current_foo_state')->first();
        $this->assertTrue($product->current_foo_state_exists);
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
class MorphOneOfManyTestProduct extends Eloquent
{
    protected $table = 'products';
    protected $guarded = [];
    public $timestamps = false;

    public function states()
    {
        return $this->morphMany(MorphOneOfManyTestState::class, 'stateful');
    }

    public function current_state()
    {
        return $this->morphOne(MorphOneOfManyTestState::class, 'stateful')->ofMany();
    }

    public function current_foo_state()
    {
        return $this->morphOne(MorphOneOfManyTestState::class, 'stateful')->ofMany(
            ['id' => 'max'],
            function ($q) {
                $q->where('type', 'foo');
            }
        );
    }
}

class MorphOneOfManyTestState extends Eloquent
{
    protected $table = 'states';
    protected $guarded = [];
    public $timestamps = false;
    protected $fillable = ['state', 'type'];
}
