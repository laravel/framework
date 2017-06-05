<?php

namespace Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\MassAssignmentException;

class DatabaseEloquentTraitProperties extends TestCase
{
    public function setUp()
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
        $this->schema()->create('flights', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('is_admin')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('flights');
    }

    /**
     * Tests...
     */
    public function testNameCanBeFilled()
    {
        $flight = TestFlightsWithTraitFillable::create(['name' => 'Airline supreme 2000']);

        $this->assertTrue($flight->wasRecentlyCreated);
    }

    public function testNameCannotBeFilled()
    {
        $this->expectException(MassAssignmentException::class);
        TestFlightsWithoutTrait::create(['name' => 'Airline supreme 2000']);
    }

    public function testCanAppend()
    {
        $flight = TestFlightsWithTraitAppends::create(['name' => 'Airline supreme 2000']);

        $this->assertArrayHasKey('active', $flight->toArray());
    }

    public function testCanHides()
    {
        $flight = TestFlightWithTraitHides::create(['name' => 'Airline supreme 2000']);

        $this->assertArrayNotHasKey('name', $flight->toArray());
    }

    public function testCanGuard()
    {
        // As name is not nullable, we will get a query exception, because we are not totally guarded a
        // mass assignment exception is not thrown.
        $this->expectException(QueryException::class);
        TestFlightWithTraitGuard::create(['name' => 'Airline supreme 2000']);
    }

    public function testCanCast()
    {
        $flight = TestFlightWithTraitCast::create(['name' => 'Airline supreme 2000', 'is_admin' => true]);

        $this->assertTrue(TestFlightWithTraitCast::find($flight->id)->is_admin === true);
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
class TestFlightsWithoutTrait extends Eloquent
{
    protected $table = 'flights';
}

class TestFlightsWithTraitFillable extends Eloquent
{
    use NameFillable;

    protected $table = 'flights';
}

trait NameFillable
{
    public $fillableNameFillable = ['name'];
}

class TestFlightsWithTraitAppends extends Eloquent
{
    use AttributeAppends;

    protected $guarded = [];

    protected $table = 'flights';
}

trait AttributeAppends
{
    public $appendsAttributeAppends = ['active'];

    public function getActiveAttribute()
    {
        return true;
    }
}

class TestFlightWithTraitHides extends Eloquent
{
    use AttributeHides;

    protected $guarded = [];

    protected $table = 'flights';
}

trait AttributeHides
{
    public $hiddenAttributeHides = ['name'];
}

class TestFlightWithTraitGuard extends Eloquent
{
    use AttributeGuardName;

    protected $guarded = [];

    protected $table = 'flights';
}

trait AttributeGuardName
{
    protected $guardedAttributeGuardName = ['name'];
}

class TestFlightWithTraitCast extends Eloquent
{
    use AttributeCast;

    protected $guarded = [];

    protected $table = 'flights';
}

trait AttributeCast
{
    public $castsAttributeCast = ['is_admin' => 'boolean'];
}
