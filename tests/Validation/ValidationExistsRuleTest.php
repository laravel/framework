<?php

namespace Illuminate\Tests\Validation;

use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Validation\DatabasePresenceVerifier;

class ValidationExistsRuleTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
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

    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new Exists('table');
        $rule->where('foo', 'bar');
        $this->assertEquals('exists:table,NULL,foo,bar', (string) $rule);

        $rule = new Exists('table', 'column');
        $rule->where('foo', 'bar');
        $this->assertEquals('exists:table,column,foo,bar', (string) $rule);
    }

    public function testItChoosesValidRecordsUsingWhereInRule()
    {
        $rule = new Exists('users', 'id');
        $rule->whereIn('type', ['foo', 'bar']);

        EloquentTestUser::create(['id' => '1', 'type' => 'foo']);
        EloquentTestUser::create(['id' => '2', 'type' => 'bar']);
        EloquentTestUser::create(['id' => '3', 'type' => 'baz']);
        EloquentTestUser::create(['id' => '4', 'type' => 'other']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => $rule]);
        $v->setPresenceVerifier(new DatabasePresenceVerifier(Eloquent::getConnectionResolver()));

        $v->setData(['id' => 1]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 2]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 3]);
        $this->assertFalse($v->passes());
        $v->setData(['id' => 4]);
        $this->assertFalse($v->passes());
    }

    public function testItChoosesValidRecordsUsingWhereNotInRule()
    {
        $rule = new Exists('users', 'id');
        $rule->whereNotIn('type', ['foo', 'bar']);

        EloquentTestUser::create(['id' => '1', 'type' => 'foo']);
        EloquentTestUser::create(['id' => '2', 'type' => 'bar']);
        EloquentTestUser::create(['id' => '3', 'type' => 'baz']);
        EloquentTestUser::create(['id' => '4', 'type' => 'other']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => $rule]);
        $v->setPresenceVerifier(new DatabasePresenceVerifier(Eloquent::getConnectionResolver()));

        $v->setData(['id' => 1]);
        $this->assertFalse($v->passes());
        $v->setData(['id' => 2]);
        $this->assertFalse($v->passes());
        $v->setData(['id' => 3]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 4]);
        $this->assertTrue($v->passes());
    }

    public function testItChoosesValidRecordsUsingWhereNotInAndWhereNotInRulesTogether()
    {
        $rule = new Exists('users', 'id');
        $rule->whereIn('type', ['foo', 'bar', 'baz'])->whereNotIn('type', ['foo', 'bar']);

        EloquentTestUser::create(['id' => '1', 'type' => 'foo']);
        EloquentTestUser::create(['id' => '2', 'type' => 'bar']);
        EloquentTestUser::create(['id' => '3', 'type' => 'baz']);
        EloquentTestUser::create(['id' => '4', 'type' => 'other']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => $rule]);
        $v->setPresenceVerifier(new DatabasePresenceVerifier(Eloquent::getConnectionResolver()));

        $v->setData(['id' => 1]);
        $this->assertFalse($v->passes());
        $v->setData(['id' => 2]);
        $this->assertFalse($v->passes());
        $v->setData(['id' => 3]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 4]);
        $this->assertFalse($v->passes());
    }

    public function testWhenTrueGivenCallbackAppliesToQueryCallbacks()
    {
        $rule = new Exists('users', 'id');
        $fired = false;
        $result = $rule->when(true, function () use (&$fired) {
            $fired = true;
        });

        $this->assertEquals($result, $rule);

        $this->assertFalse($fired);
        $this->assertCount(1, $rule->queryCallbacks());

        $rule->queryCallbacks()[0]();
        $this->assertTrue($fired);
    }

    public function testWhenFalseGivenCallbackNotAppliesToQueryCallbacks()
    {
        $rule = new Exists('users', 'id');
        $fired = false;
        $rule->when(false, function () use (&$fired) {
            $fired = true;
        });

        $this->assertFalse($fired);
        $this->assertCount(0, $rule->queryCallbacks());
    }

    public function testWhenMoreThenOnce()
    {
        $callback = function () {
        };
        $rule = new Exists('users', 'id');
        $rule->when(true, $callback)
            ->when(true, $callback)
            ->when(true, $callback)
            ->when(false, $callback);

        $this->assertCount(3, $rule->queryCallbacks());
    }

    public function testWhenDefaultCallbackNotCalledIfTrueGiven()
    {
        $rule = new Exists('users', 'id');

        $rule->when(true, function () {
            return 'callback';
        }, function () {
            return 'default';
        });

        $this->assertCount(1, $rule->queryCallbacks());
        $this->assertEquals('callback', $rule->queryCallbacks()[0]());
    }

    public function testWhenDefaultCallbackCalledWhenFalseGiven()
    {
        $rule = new Exists('users', 'id');

        $rule->when(false, function () {
            return 'callback';
        }, function () {
            return 'default';
        });

        $this->assertCount(1, $rule->queryCallbacks());
        $this->assertEquals('default', $rule->queryCallbacks()[0]());
    }

    public function testUnlessAppliesToQueryCallbacks()
    {
        $rule = new Exists('users', 'id');
        $fired = false;
        $result = $rule->unless(false, function () use (&$fired) {
            $fired = true;
        });

        $this->assertEquals($result, $rule);

        $this->assertFalse($fired);
        $this->assertCount(1, $rule->queryCallbacks());

        $rule->queryCallbacks()[0]();
        $this->assertTrue($fired);
    }

    public function testUnlessTwiceAndCombinedWithPositive()
    {
        $callback = function () {
        };
        $rule = new Exists('users', 'id');
        $rule->unless(false, $callback)
            ->unless(false, $callback)
            ->unless(false, $callback)
            ->unless(true, $callback);

        $this->assertCount(3, $rule->queryCallbacks());
    }

    public function testUnlessDefaultNotFiredWhenFalseGiven()
    {
        $rule = new Exists('users', 'id');
        $rule->unless(false, function () {
            return 'callback';
        }, function () {
            return 'default';
        });

        $this->assertCount(1, $rule->queryCallbacks());
        $this->assertEquals('callback', $rule->queryCallbacks()[0]());
    }

    public function testUnlessDefaultCalledWhenTrueGiven()
    {
        $rule = new Exists('users', 'id');
        $rule->unless(true, function () {
            return 'callback';
        }, function () {
            return 'default';
        });

        $this->assertCount(1, $rule->queryCallbacks());
        $this->assertEquals('default', $rule->queryCallbacks()[0]());
    }

    public function testWhenWithWhere()
    {
        $rule = new Exists('users', 'id');
        $rule->when(true, function (Builder $query) {
            $query->where('type', 'foo');
        });

        EloquentTestUser::create(['id' => '1', 'type' => 'foo']);
        EloquentTestUser::create(['id' => '2', 'type' => 'bar']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => $rule]);
        $v->setPresenceVerifier(new DatabasePresenceVerifier(Eloquent::getConnectionResolver()));

        $v->setData(['id' => 1]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 2]);
        $this->assertFalse($v->passes());
    }

    public function testWithDefaultCallbackWithWhere()
    {
        $rule = new Exists('users', 'id');
        $rule->when(false, function (Builder $query) {
            throw new \RuntimeException('This callback will never call');
        }, function (Builder $query) {
            $query->where('type', 'foo');
        });

        EloquentTestUser::create(['id' => '1', 'type' => 'foo']);
        EloquentTestUser::create(['id' => '2', 'type' => 'bar']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => $rule]);
        $v->setPresenceVerifier(new DatabasePresenceVerifier(Eloquent::getConnectionResolver()));

        $v->setData(['id' => 1]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 2]);
        $this->assertFalse($v->passes());
    }

    public function testUnlessWithWhere()
    {
        $rule = new Exists('users', 'id');
        $rule->unless(false, function (Builder $query) {
            $query->where('type', 'foo');
        });

        EloquentTestUser::create(['id' => '1', 'type' => 'foo']);
        EloquentTestUser::create(['id' => '2', 'type' => 'bar']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => $rule]);
        $v->setPresenceVerifier(new DatabasePresenceVerifier(Eloquent::getConnectionResolver()));

        $v->setData(['id' => 1]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 2]);
        $this->assertFalse($v->passes());
    }

    public function testUnlessDefaultCallbackWithWhere()
    {
        $rule = new Exists('users', 'id');
        $rule->unless(true, function () {
            throw new \RuntimeException('This callback will never call.');
        }, function (Builder $query) {
            $query->where('type', 'foo');
        });

        EloquentTestUser::create(['id' => '1', 'type' => 'foo']);
        EloquentTestUser::create(['id' => '2', 'type' => 'bar']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => $rule]);
        $v->setPresenceVerifier(new DatabasePresenceVerifier(Eloquent::getConnectionResolver()));

        $v->setData(['id' => 1]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 2]);
        $this->assertFalse($v->passes());
    }

    protected function createSchema()
    {
        $this->schema('default')->create('users', function ($table) {
            $table->unsignedInteger('id');
            $table->string('type');
        });
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

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return $this->getConnectionResolver()->connection($connection);
    }

    /**
     * Get connection resolver.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    protected function getConnectionResolver()
    {
        return Eloquent::getConnectionResolver();
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema('default')->drop('users');
    }

    public function getIlluminateArrayTranslator()
    {
        return new \Illuminate\Translation\Translator(
            new \Illuminate\Translation\ArrayLoader,
            'en'
        );
    }
}

/**
 * Eloquent Models.
 */
class EloquentTestUser extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
}
