<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\AllExists;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ValidationAllExistsRuleTest extends TestCase
{
    protected function setUpTranslator(): void
    {
        $container = Container::getInstance();

        $container->bind('translator',
            fn() => new Translator(
                new ArrayLoader, 'en'
            )
        );

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function setUpDB(): void
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

    public function setUp(): void
    {
        $this->setUpTranslator();
        $this->setUpDB();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('tags', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
        });
    }

    public function testValidationPassWhenAllValuesExist()
    {
        $this->seedData();

        $v = new Validator(
            resolve('translator'),
            [
                'tags_id' => [2, 3],
            ],
            [
                'tags_id' => (new AllExists(AllExistsTestTag::class, 'id'))->withoutTrashed()
            ]
        );

        $this->assertTrue($v->passes());
    }

    public function testValidationPassWhenAllValuesExistWithWhereCondition()
    {
        $this->seedData();

        $v = new Validator(
            resolve('translator'),
            [
                'tags_id' => [2, 3],
            ],
            [
                'tags_id' => (new AllExists(AllExistsTestTag::class, 'id'))
                    ->where('name','ab')
            ]
        );

        $this->assertTrue($v->passes());
    }

    public function testValidationPassWhenAllValuesExistWithClosure()
    {
        $this->seedData();

        $v = new Validator(
            resolve('translator'),
            [
                'tags_id' => [2, 3],
            ],
            [
                'tags_id' => (new AllExists(AllExistsTestTag::class, 'id'))
                    ->using(fn($q) => $q->where('name', 'ab'))
            ]
        );

        $this->assertTrue($v->passes());
    }

    public function testValidationFailsWhenAllValuesNotExist()
    {
        $this->seedData();

        $v = new Validator(
            resolve('translator'),
            [
                'tags_id' => [1, 2, 3],
            ],
            [
                'tags_id' => (new AllExists(AllExistsTestTag::class, 'id'))->withoutTrashed()
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertEquals(['tags id are invalid.'], $v->messages()->get('tags_id'));
    }

    public function testValidationFailsWhenAllValuesExistWithWhereCondition()
    {
        $this->seedData();

        $v = new Validator(
            resolve('translator'),
            [
                'tags_id' => [2, 3, 4],
            ],
            [
                'tags_id' => (new AllExists(AllExistsTestTag::class, 'id'))
                    ->where('name','ab')
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertEquals(['tags id are invalid.'], $v->messages()->get('tags_id'));
    }

    public function testValidationFailsWhenAllValuesNotExistWithClosure()
    {
        $this->seedData();

        $v = new Validator(
            resolve('translator'),
            [
                'tags_id' => [2, 3, 4],
            ],
            [
                'tags_id' => (new AllExists(AllExistsTestTag::class, 'id'))
                    ->using(fn ($q) => $q->where('name', 'ab'))
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertEquals(['tags id are invalid.'], $v->messages()->get('tags_id'));
    }

    public function testThrowsExceptionWhenValueIsNotArray()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('[AllExistsRule] : validation value must be array.');

         $v = new Validator(
            resolve('translator'),
            [
                'tags_id' => 'something',
            ],
            [
                'tags_id' => new AllExists(AllExistsTestTag::class, 'id')
            ]
        );
         $v->passes();

    }


    /**
     * Helpers...
     */
    protected function seedData()
    {
        AllExistsTestTag::query()->insert([
            ['id' => 1, 'name' => 'za', 'deleted_at' => date('Y-m-d')],
            ['id' => 2, 'name' => 'ab', 'deleted_at' => null],
            ['id' => 3, 'name' => 'ab', 'deleted_at' => null],
            ['id' => 4, 'name' => 'vb', 'deleted_at' => null],
        ]);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
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

    protected function tearDownTranslator(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);
    }

    protected function tearDownDB()
    {
        $this->schema()->drop('tags');
    }

    protected function tearDown(): void
    {
        $this->tearDownTranslator();
        $this->tearDownDB();
    }
}

class AllExistsTestTag extends Eloquent
{
    use SoftDeletes;

    protected $table = 'tags';
    protected $guarded = [];
}
