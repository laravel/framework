<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationExistsRuleTest extends TestCase
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

    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new Exists('table');
        $rule->where('foo', 'bar');
        $this->assertSame('exists:table,NULL,foo,"bar"', (string) $rule);

        $rule = new Exists(User::class);
        $rule->where('foo', 'bar');
        $this->assertSame('exists:users,NULL,foo,"bar"', (string) $rule);

        $rule = new Exists(UserWithPrefixedTable::class);
        $rule->where('foo', 'bar');
        $this->assertSame('exists:'.UserWithPrefixedTable::class.',NULL,foo,"bar"', (string) $rule);

        $rule = new Exists('table', 'column');
        $rule->where('foo', 'bar');
        $this->assertSame('exists:table,column,foo,"bar"', (string) $rule);

        $rule = new Exists(User::class, 'column');
        $rule->where('foo', 'bar');
        $this->assertSame('exists:users,column,foo,"bar"', (string) $rule);

        $rule = new Exists(UserWithConnection::class, 'column');
        $rule->where('foo', 'bar');
        $this->assertSame('exists:mysql.users,column,foo,"bar"', (string) $rule);

        $rule = new Exists('Illuminate\Tests\Validation\User', 'column');
        $rule->where('foo', 'bar');
        $this->assertSame('exists:users,column,foo,"bar"', (string) $rule);

        $rule = new Exists(NoTableNameModel::class, 'column');
        $rule->where('foo', 'bar');
        $this->assertSame('exists:no_table_name_models,column,foo,"bar"', (string) $rule);

        $rule = new Exists(ClassWithRequiredConstructorParameters::class, 'column');
        $rule->where('foo', 'bar');
        $this->assertSame('exists:'.ClassWithRequiredConstructorParameters::class.',column,foo,"bar"', (string) $rule);
    }

    public function testItChoosesValidRecordsUsingWhereInRule()
    {
        $rule = new Exists('users', 'id');
        $rule->whereIn('type', ['foo', 'bar']);

        User::create(['id' => '1', 'type' => 'foo']);
        User::create(['id' => '2', 'type' => 'bar']);
        User::create(['id' => '3', 'type' => 'baz']);
        User::create(['id' => '4', 'type' => 'other']);

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

        // array values
        $v->setData(['id' => [1, 2]]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => [3, 4]]);
        $this->assertFalse($v->passes());
    }

    public function testItChoosesValidRecordsUsingWhereNotInRule()
    {
        $rule = new Exists('users', 'id');
        $rule->whereNotIn('type', ['foo', 'bar']);

        User::create(['id' => '1', 'type' => 'foo']);
        User::create(['id' => '2', 'type' => 'bar']);
        User::create(['id' => '3', 'type' => 'baz']);
        User::create(['id' => '4', 'type' => 'other']);

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

        // array values
        $v->setData(['id' => [1, 2]]);
        $this->assertFalse($v->passes());
        $v->setData(['id' => [3, 4]]);
        $this->assertTrue($v->passes());
    }

    public function testItChoosesValidRecordsUsingConditionalModifiers()
    {
        $rule = new Exists('users', 'id');
        $rule->when(true, function ($rule) {
            $rule->whereNotIn('type', ['foo', 'bar']);
        });
        $rule->unless(true, function ($rule) {
            $rule->whereNotIn('type', ['baz', 'other']);
        });

        User::create(['id' => '1', 'type' => 'foo']);
        User::create(['id' => '2', 'type' => 'bar']);
        User::create(['id' => '3', 'type' => 'baz']);
        User::create(['id' => '4', 'type' => 'other']);

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

        // array values
        $v->setData(['id' => [1, 2]]);
        $this->assertFalse($v->passes());
        $v->setData(['id' => [3, 4]]);
        $this->assertTrue($v->passes());
    }

    public function testItChoosesValidRecordsUsingWhereNotInAndWhereNotInRulesTogether()
    {
        $rule = new Exists('users', 'id');
        $rule->whereIn('type', ['foo', 'bar', 'baz'])->whereNotIn('type', ['foo', 'bar']);

        User::create(['id' => '1', 'type' => 'foo']);
        User::create(['id' => '2', 'type' => 'bar']);
        User::create(['id' => '3', 'type' => 'baz']);
        User::create(['id' => '4', 'type' => 'other']);
        User::create(['id' => '5', 'type' => 'baz']);

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
        $v->setData(['id' => 5]);
        $this->assertTrue($v->passes());

        // array values
        $v->setData(['id' => [1, 2, 4]]);
        $this->assertFalse($v->passes());
        $v->setData(['id' => [3, 5]]);
        $this->assertTrue($v->passes());
    }

    public function testItChoosesValidRecordsUsingWhereNotRule()
    {
        $rule = new Exists('users', 'id');

        $rule->whereNot('type', 'baz');

        User::create(['id' => '1', 'type' => 'foo']);
        User::create(['id' => '2', 'type' => 'bar']);
        User::create(['id' => '3', 'type' => 'baz']);
        User::create(['id' => '4', 'type' => 'other']);
        User::create(['id' => '5', 'type' => 'baz']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => $rule]);
        $v->setPresenceVerifier(new DatabasePresenceVerifier(Eloquent::getConnectionResolver()));

        $v->setData(['id' => 3]);
        $this->assertFalse($v->passes());

        $v->setData(['id' => 4]);
        $this->assertTrue($v->passes());
    }

    public function testItIgnoresSoftDeletes()
    {
        $rule = new Exists('table');
        $rule->withoutTrashed();
        $this->assertSame('exists:table,NULL,deleted_at,"NULL"', (string) $rule);

        $rule = new Exists('table');
        $rule->withoutTrashed('softdeleted_at');
        $this->assertSame('exists:table,NULL,softdeleted_at,"NULL"', (string) $rule);
    }

    public function testItOnlyTrashedSoftDeletes()
    {
        $rule = new Exists('table');
        $rule->onlyTrashed();
        $this->assertSame('exists:table,NULL,deleted_at,"NOT_NULL"', (string) $rule);

        $rule = new Exists('table');
        $rule->onlyTrashed('softdeleted_at');
        $this->assertSame('exists:table,NULL,softdeleted_at,"NOT_NULL"', (string) $rule);
    }

    public function testItIsAPartOfListRules()
    {
        $rule = new Exists('users', 'id');

        User::create(['id' => '1', 'type' => 'foo']);
        User::create(['id' => '2', 'type' => 'bar']);
        User::create(['id' => '3', 'type' => 'baz']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => ['required', $rule]]);
        $v->setPresenceVerifier(new DatabasePresenceVerifier(Eloquent::getConnectionResolver()));

        $v->setData(['id' => 1]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 2]);
        $this->assertTrue($v->passes());
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
    protected function tearDown(): void
    {
        $this->schema('default')->drop('users');
    }

    public function getIlluminateArrayTranslator()
    {
        return new Translator(
            new ArrayLoader, 'en'
        );
    }
}

/**
 * Eloquent Models.
 */
class User extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
}

class UserWithPrefixedTable extends Eloquent
{
    protected $table = 'public.users';
    protected $guarded = [];
    public $timestamps = false;
}

class UserWithConnection extends User
{
    protected $connection = 'mysql';
}

class NoTableNameModel extends Eloquent
{
    protected $guarded = [];
    public $timestamps = false;
}

class ClassWithRequiredConstructorParameters
{
    private $bar;
    private $baz;

    public function __construct($bar, $baz)
    {
        $this->bar = $bar;
        $this->baz = $baz;
    }
}
