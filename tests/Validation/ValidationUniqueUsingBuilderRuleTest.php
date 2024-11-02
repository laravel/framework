<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\UniqueUsingBuilder;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ValidationUniqueUsingBuilderRuleTest extends TestCase
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

    #[TestWith(['id'])]
    #[TestWith(['NULL'])]
    public function testItChoosesUniqueRecordsUsingWhereNotInRule(string $column): void
    {
        $rule = new UniqueUsingBuilder(UserForUniqueRule::query()->whereNotIn('type', ['foo', 'bar']), $column);

        UserForUniqueRule::query()->create(['id' => '1', 'type' => 'foo']);
        UserForUniqueRule::query()->create(['id' => '2', 'type' => 'bar']);
        UserForUniqueRule::query()->create(['id' => '3', 'type' => 'baz']);
        UserForUniqueRule::query()->create(['id' => '4', 'type' => 'other']);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => $rule]);

        $v->setData(['id' => 1]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 2]);
        $this->assertTrue($v->passes());
        $v->setData(['id' => 3]);
        $this->assertFalse($v->passes());
        $v->setData(['id' => 4]);
        $this->assertFalse($v->passes());
    }

    public function testItDoesNotQueryDatabaseWhenPreviousRuleFailed(): void
    {
        $rule = new UniqueUsingBuilder(UserForUniqueRule::query(), 'id');

        DB::enableQueryLog();

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['id' => ['uuid', $rule]]);

        $v->setData(['id' => 1]);
        $this->assertFalse($v->passes());

        $this->assertEmpty(DB::getQueryLog());
    }

    protected function createSchema(): void
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
class UserForUniqueRule extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
}
