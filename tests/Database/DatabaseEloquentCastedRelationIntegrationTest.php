<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Builder;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentCastedRelationIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->migrateDefault();
        $this->seedDefaultData();
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('model_a_s');
        $this->schema()->drop('model_b_s');
    }

    /**
     * Migrate tables for classes with a Laravel "default" HasOneThrough setup.
     */
    protected function migrateDefault()
    {
        $this->schema()->create('model_a_s', function ($table) {
            $table->increments('id');
            $table->string('uuid')->unique();
        });

        $this->schema()->create('model_b_s', function ($table) {
            $table->increments('id');
            $table->string('model_a_uuid');
            $table->foreign('model_a_uuid')->references('uuid')->on('model_a_s');
        });

        $this->schema()->create('model_c_s', function ($table) {
            $table->increments('id');
            $table->string('uuid')->unique();
        });

        $this->schema()->create('model_a_model_c', function ($table) {
            $table->increments('id');
            $table->string('model_a_uuid');
            $table->foreign('model_a_uuid')->references('uuid')->on('model_a_s');
            $table->string('model_c_uuid');
            $table->foreign('model_c_uuid')->references('uuid')->on('model_c_s');
        });
    }

    /**
     * Seed data for a default HasOneThrough setup.
     */
    protected function seedDefaultData()
    {
        $modelA1 = ModelA::create(['uuid' => 'A-XXX-XXX-1']);
        $modelA2 = ModelA::create(['uuid' => 'A-XXX-XXX-2']);
        ModelB::create(['model_a_uuid' => 'A-XXX-XXX-1']);
        ModelB::create(['model_a_uuid' => 'A-XXX-XXX-1']);
        $modelC1 = ModelC::create(['uuid' => 'C-XXX-XXX-1']);
        $modelC2 = ModelC::create(['uuid' => 'C-XXX-XXX-2']);
    }

    /**
     * Get a database connection instance.
     *
     * @return Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    public function testRelationOneToOneAToB()
    {
        /** @var ModelA $modelA */
        $modelA = ModelA::all()->first();

        $this->assertInstanceOf(ModelB::class, $modelA->oneModelB);
        $this->assertSame($modelA->uuid->value, $modelA->oneModelB->model_a_uuid->value);
    }

    public function testRelationOneToOneBToA()
    {
        /** @var ModelB $modelB */
        $modelB = ModelB::all()->first();

        $this->assertInstanceOf(ModelA::class, $modelB->oneModelA);
        $this->assertSame($modelB->model_a_uuid->value, $modelB->oneModelA->uuid->value);
    }

    public function testRelationOneToManyAToB()
    {
        /** @var ModelA $modelA */
        $modelA = ModelA::all()->first();
        $modelBs = $modelA->manyModelB;

        $this->assertEquals(2, $modelBs->count());

        $this->assertInstanceOf(ModelB::class, $modelBs->get(0));
        $this->assertSame($modelA->uuid->value, $modelBs->get(0)->model_a_uuid->value);

        $this->assertInstanceOf(ModelB::class, $modelBs->get(1));
        $this->assertSame($modelA->uuid->value, $modelBs->get(1)->model_a_uuid->value);
    }

    public function testRelationManyToOneBToA()
    {
        /** @var Collection $modelBs */
        $modelBs = ModelB::all();

        $this->assertEquals(2, $modelBs->count());

        $this->assertInstanceOf(ModelA::class, $modelBs->get(0)->oneModelA);
        $this->assertSame($modelBs->get(0)->model_a_uuid->value, $modelBs->get(0)->oneModelA->uuid->value);

        $this->assertInstanceOf(ModelA::class, $modelBs->get(1)->oneModelA);
        $this->assertSame($modelBs->get(1)->model_a_uuid->value, $modelBs->get(1)->oneModelA->uuid->value);
    }

    public function testRelationManyToManyBToC()
    {
        /** @var ModelA $modelA */
        $modelA = ModelA::all()->first();

        /* Create relations */
        $modelA->manyModelC()->saveMany([
            ModelC::firstWhere(['uuid' => 'C-XXX-XXX-1']),
            ModelC::firstWhere(['uuid' => 'C-XXX-XXX-2'])]);

        $modelCs = $modelA->manyModelC;

        $this->assertEquals(2, $modelCs->count());
        $this->assertSame('C-XXX-XXX-1', $modelCs->get(0)->uuid->value);
        $this->assertSame('C-XXX-XXX-2', $modelCs->get(1)->uuid->value);
    }

    public function testRelationManyToManyCToB()
    {
        /** @var ModelC $modelC */
        $modelC = ModelC::all()->first();

        /* Create relations */
        $modelC->manyModelA()->saveMany([
            ModelA::firstWhere(['uuid' => 'A-XXX-XXX-1']),
            ModelA::firstWhere(['uuid' => 'A-XXX-XXX-2'])]);

        $modelAs = $modelC->manyModelA;

        $this->assertEquals(2, $modelAs->count());
        $this->assertSame('A-XXX-XXX-1', $modelAs->get(0)->uuid->value);
        $this->assertSame('A-XXX-XXX-2', $modelAs->get(1)->uuid->value);
    }
}

/**
 * @property object uuid
 * @property ModelB oneModelB
 * @property Collection manyModelB
 * @property Collection manyModelC
 */
class ModelA extends Model
{
    protected $casts = ['uuid' => ToObject::class];
    protected $fillable = ['uuid'];
    public $timestamps = false;

    public function oneModelB()
    {
        return $this->hasOne(ModelB::class, 'model_a_uuid', 'uuid');
    }

    public function manyModelB()
    {
        return $this->hasMany(ModelB::class, 'model_a_uuid', 'uuid');
    }

    public function manyModelC()
    {
        return $this->belongsToMany(
            ModelC::class,
            'model_a_model_c',
            'model_a_uuid',
            'model_c_uuid');
    }
}

/**
 * @property ModelA oneModelA
 * @property object model_a_uuid
 */
class ModelB extends Model
{
    protected $casts = ['model_a_uuid' => ToObject::class];
    protected $fillable = ['model_a_uuid'];
    public $timestamps = false;

    public function oneModelA()
    {
        return $this->belongsTo(ModelA::class, 'model_a_uuid', 'uuid');
    }
}

/**
 * @property Collection manyModelA
 * @property object uuid
 */
class ModelC extends Model
{
    protected $casts = ['uuid' => ToObject::class];
    protected $fillable = ['uuid'];
    public $timestamps = false;

    public function manyModelA()
    {
        return $this->belongsToMany(
            ModelA::class,
            'model_a_model_c',
            'model_c_uuid',
            'model_a_uuid');
    }
}

class ToObject implements CastsAttributes
{
    public function set($model, string $key, $value, array $attributes): string
    {
        return is_string($value) ? $value : strval($value->value ?? null);
    }

    public function get($model, string $key, $value, array $attributes): object
    {
        return (object)['value' => $value];
    }
}
