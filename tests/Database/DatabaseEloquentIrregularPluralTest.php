<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentIrregularPluralTest extends TestCase
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

        $this->schema()->create('irregular_plural_mottoes', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->schema()->create('cool_mottoes', function ($table) {
            $table->integer('irregular_plural_motto_id');
            $table->integer('cool_motto_id');
            $table->string('cool_motto_type');
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->schema()->drop('irregular_plural_tokens');
        $this->schema()->drop('irregular_plural_humans');
        $this->schema()->drop('irregular_plural_human_irregular_plural_token');

        Carbon::setTestNow(null);
    }

    protected function schema()
    {
        $connection = Model::getConnectionResolver()->connection();

        return $connection->getSchemaBuilder();
    }

    public function testItPluralizesTheTableName()
    {
        $model = new IrregularPluralHuman;

        $this->assertSame('irregular_plural_humans', $model->getTable());
    }

    public function testItTouchesTheParentWithAnIrregularPlural()
    {
        Carbon::setTestNow('2018-05-01 12:13:14');

        IrregularPluralHuman::create(['email' => 'taylorotwell@gmail.com']);

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

    public function testItPluralizesMorphToManyRelationships()
    {
        $human = IrregularPluralHuman::create(['email' => 'bobby@example.com']);

        $human->mottoes()->create(['name' => 'Real eyes realize real lies']);

        $motto = IrregularPluralMotto::query()->first();

        $this->assertSame('Real eyes realize real lies', $motto->name);
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

    public function mottoes()
    {
        return $this->morphToMany(IrregularPluralMotto::class, 'cool_motto');
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

class IrregularPluralMotto extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function irregularPluralHumans()
    {
        return $this->morphedByMany(IrregularPluralHuman::class, 'cool_motto');
    }
}
