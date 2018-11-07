<?php

namespace Illuminate\Tests\Database;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

class DatabaseEloquentIrregularPluralTest extends TestCase
{
    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();
        $this->createSchema();
    }

    public function createSchema()
    {
        $this->schema()->create('irregular_plural_humans', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->timestamps();
        });

        $this->schema()->create('irregular_plural_tokens', function ($table) {
            $table->increments('id');
            $table->string('title');
        });

        $this->schema()->create('irregular_plural_human_irregular_plural_token', function ($table) {
            $table->integer('irregular_plural_human_id')->unsigned();
            $table->integer('irregular_plural_token_id')->unsigned();
        });
    }

    public function tearDown()
    {
        $this->schema()->drop('irregular_plural_tokens');
        $this->schema()->drop('irregular_plural_humans');
        $this->schema()->drop('irregular_plural_human_irregular_plural_token');
    }

    protected function schema()
    {
        $connection = Model::getConnectionResolver()->connection();

        return $connection->getSchemaBuilder();
    }

    /** @test */
    function it_pluralizes_the_table_name()
    {
        $model = new IrregularPluralHuman();

        $this->assertSame('irregular_plural_humans', $model->getTable());
    }

    /** @test */
    function it_touches_the_parent_with_an_irregular_plural()
    {
        Carbon::setTestNow('2018-05-01 12:13:14');

        IrregularPluralHuman::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);

        IrregularPluralToken::insert([
            ['title' => 'The title'],
        ]);

        $human = IrregularPluralHuman::query()->first();

        $tokenIds = IrregularPluralToken::pluck('id');

        Carbon::setTestNow('2018-05-01 15:16:17');

        $human->irregularPluralTokens()->sync($tokenIds);

        $human->refresh();

        $this->assertSame('2018-05-01 12:13:14', (string) $human->created_at);
        $this->assertSame('2018-05-01 15:16:17', (string) $human->updated_at);
    }
}

class IrregularPluralHuman extends Model
{
    protected $guarded = [];

    public function irregularPluralTokens()
    {
        return $this->belongsToMany(
            IrregularPluralToken::class,
            'irregular_plural_human_irregular_plural_token',
            'irregular_plural_token_id',
            'irregular_plural_human_id'
        );
    }
}

class IrregularPluralToken extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $touches = [
        'irregularPluralHumans',
    ];
}
