<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationModelExistsRuleTest extends TestCase
{
    /**
     * Setup the database schema.
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

    public function testItCanPassAnEloquentBuilderInstance()
    {
        $rule = Rule::modelExists(UserModel::query());
        $this->assertInstanceOf(Builder::class, $rule->getQueryBuilder());
        $this->assertInstanceOf(UserModel::class, $rule->getQueryBuilder()->getModel());
    }

    public function testItCanPassAModelInstance()
    {
        $rule = Rule::modelExists(new UserModel);
        $this->assertInstanceOf(Builder::class, $rule->getQueryBuilder());
        $this->assertInstanceOf(UserModel::class, $rule->getQueryBuilder()->getModel());
    }

    public function testItCanPassAModelClassName()
    {
        $rule = Rule::modelExists(UserModel::class);
        $this->assertInstanceOf(Builder::class, $rule->getQueryBuilder());
        $this->assertInstanceOf(UserModel::class, $rule->getQueryBuilder()->getModel());
    }

    public function testItForwardsCallsToTheQueryBuilder()
    {
        $rule = Rule::modelExists(UserModel::class);
        $rule->where('foo', 'bar');
        $this->assertSame('select * from "users" where "foo" = ?', $rule->getQueryBuilder()->toSql());
    }

    public function testPassesWhenRecordExists()
    {
        $rule = Rule::modelExists(UserModel::class);

        UserModel::create(['id' => 1, 'type' => 'foo']);

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.exists' => 'The selected :attribute is invalid.'], 'en');
        $v = new Validator($trans, ['id' => 1], ['id' => $rule]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['id' => 2], ['id' => $rule]);
        $this->assertFalse($v->passes());
        $this->assertSame('The selected id is invalid.', $v->errors()->first('id'));
    }

    public function testPassesWhenRecordExistsWithScope()
    {
        $rule = Rule::modelExists(UserModel::class)->typeFoo();

        UserModel::create(['id' => 1, 'type' => 'foo']);
        UserModel::create(['id' => 2, 'type' => 'bar']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['id' => 1], ['id' => $rule]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['id' => 2], ['id' => $rule]);
        $this->assertFalse($v->passes());
    }

    public function testPassesWhenRecordExistsWithColumn()
    {
        $rule = Rule::modelExists(UserModel::class, 'type');

        UserModel::create(['id' => 1, 'type' => 'foo']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['id' => 'foo'], ['id' => $rule]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['id' => 'bar'], ['id' => $rule]);
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
class UserModel extends Eloquent
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;

    public function scopeTypeFoo($query)
    {
        $query->where('type', 'foo');
    }
}
