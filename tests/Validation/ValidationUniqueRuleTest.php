<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationUniqueRuleTest extends TestCase
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
        $rule = new Unique('table');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,NULL,NULL,id,foo,"bar"', (string) $rule);

        $rule = new Unique(EloquentModelStub::class);
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,NULL,NULL,id,foo,"bar"', (string) $rule);

        $rule = new Unique(NoTableName::class);
        $rule->where('foo', 'bar');
        $this->assertSame('unique:no_table_names,NULL,NULL,id,foo,"bar"', (string) $rule);

        $rule = new Unique('Illuminate\Tests\Validation\NoTableName');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:no_table_names,NULL,NULL,id,foo,"bar"', (string) $rule);

        $rule = new Unique(ClassWithNonEmptyConstructor::class);
        $rule->where('foo', 'bar');
        $this->assertSame('unique:'.ClassWithNonEmptyConstructor::class.',NULL,NULL,id,foo,"bar"', (string) $rule);

        $rule = new Unique('table', 'column');
        $rule->ignore('Taylor, Otwell', 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"Taylor, Otwell",id_column,foo,"bar"', (string) $rule);

        $rule = new Unique(PrefixedTableEloquentModelStub::class);
        $this->assertSame('unique:'.PrefixedTableEloquentModelStub::class.',NULL,NULL,id', (string) $rule);

        $rule = new Unique(EloquentModelStub::class, 'column');
        $rule->ignore('Taylor, Otwell', 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"Taylor, Otwell",id_column,foo,"bar"', (string) $rule);

        $rule = new Unique(EloquentModelStub::class, 'column');
        $rule->where('foo', 'bar');
        $rule->when(true, function ($rule) {
            $rule->ignore('Taylor, Otwell', 'id_column');
        });
        $rule->unless(true, function ($rule) {
            $rule->ignore('Chris', 'id_column');
        });
        $this->assertSame('unique:table,column,"Taylor, Otwell",id_column,foo,"bar"', (string) $rule);

        $rule = new Unique('table', 'column');
        $rule->ignore('Taylor, Otwell"\'..-"', 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"Taylor, Otwell\"\\\'..-\"",id_column,foo,"bar"', (string) $rule);
        $this->assertSame('Taylor, Otwell"\'..-"', stripslashes(str_getcsv('table,column,"Taylor, Otwell\"\\\'..-\"",id_column,foo,"bar"')[2]));
        $this->assertSame('id_column', stripslashes(str_getcsv('table,column,"Taylor, Otwell\"\\\'..-\"",id_column,foo,"bar"')[3]));

        $rule = new Unique('table', 'column');
        $rule->ignore(null, 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,NULL,id_column,foo,"bar"', (string) $rule);

        $model = new EloquentModelStub(['id_column' => 1]);

        $rule = new Unique('table', 'column');
        $rule->ignore($model);
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"1",id_column,foo,"bar"', (string) $rule);

        $rule = new Unique('table', 'column');
        $rule->ignore($model, 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"1",id_column,foo,"bar"', (string) $rule);

        $rule = new Unique('table');
        $rule->where('foo', '"bar"');
        $this->assertSame('unique:table,NULL,NULL,id,foo,"""bar"""', (string) $rule);
    }

    public function testItIgnoresSoftDeletes()
    {
        $rule = new Unique('table');
        $rule->withoutTrashed();
        $this->assertSame('unique:table,NULL,NULL,id,deleted_at,"NULL"', (string) $rule);

        $rule = new Unique('table');
        $rule->withoutTrashed('softdeleted_at');
        $this->assertSame('unique:table,NULL,NULL,id,softdeleted_at,"NULL"', (string) $rule);
    }

    public function testItCorrectlyValidateDataInDatabase()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], []);
        $v->setPresenceVerifier(new DatabasePresenceVerifier(Model::getConnectionResolver()));

        ValidationUniqueRuleTestUser::query()->insert(
            [
                ['id' => '1', 'username' => 'batman', 'deleted_at' => null],
                ['id' => '2', 'username' => 'masoud', 'deleted_at' => '2021-10-12 00:00:00'],
            ]
        );

        $rule = new Unique(ValidationUniqueRuleTestUser::class, 'username');
        $v->setRules(['username' => $rule]);
        $v->setData(['username' => 'batman']);
        $this->assertFalse($v->passes());

        $rule = new Unique(ValidationUniqueRuleTestUser::class, 'username');
        $rule->ignore(1);
        $v->setRules(['username' => $rule]);
        $v->setData(['username' => 'batman']);
        $this->assertTrue($v->passes());

        $rule = new Unique(ValidationUniqueRuleTestUser::class, 'username');
        $rule->ignore(ValidationUniqueRuleTestUser::find(1));
        $v->setRules(['username' => $rule]);
        $v->setData(['username' => 'batman']);
        $this->assertTrue($v->passes());

        $rule = new Unique(ValidationUniqueRuleTestUser::class, 'username');
        $v->setRules(['username' => $rule]);
        $v->setData(['username' => 'masoud']);
        $this->assertFalse($v->passes());

        $rule = new Unique(ValidationUniqueRuleTestUser::class, 'username');
        $rule->withoutTrashed();
        $v->setRules(['username' => $rule]);
        $v->setData(['username' => 'masoud']);
        $this->assertTrue($v->passes());
    }

    protected function createSchema()
    {
        $this->schema('default')->create('users', function ($table) {
            $table->unsignedInteger('id');
            $table->string('username');
            $table->softDeletes()->nullable();
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
        return Model::getConnectionResolver();
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
class ValidationUniqueRuleTestUser extends Model
{
    use SoftDeletes;
    protected $table = 'users';
    protected $guarded = [];
}

class EloquentModelStub extends Model
{
    protected $table = 'table';
    protected $primaryKey = 'id_column';
    protected $guarded = [];
}

class PrefixedTableEloquentModelStub extends Model
{
    protected $table = 'public.table';
    protected $primaryKey = 'id_column';
    protected $guarded = [];
}

class NoTableName extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}

class ClassWithNonEmptyConstructor
{
    private $bar;
    private $baz;

    public function __construct($bar, $baz)
    {
        $this->bar = $bar;
        $this->baz = $baz;
    }
}
