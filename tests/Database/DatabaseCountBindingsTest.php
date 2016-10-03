<?php

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\ScopeInterface;

class DatabaseCountBindingsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Setup the database schema.
     * @return void
     */
    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Tear down the database schema.
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('pages');
        $this->schema()->drop('page_translations');
        $this->schema()->drop('blocks');
        $this->schema()->drop('block_translations');
    }

    public function testBindingCount()
    {
        Page::create(['id' => 1, 'uri' => 'test1']);
        PageTranslation::create(['id' => 1, 'page_id' => 1, 'locale' => 'en', 'content' => 'Lorem ipsum dolor sit amet']);
        Block::create(['id' => 1, 'page_id' => 1, 'type' => 'static']);
        BlockTranslation::create(['id' => 1, 'block_id' => 1, 'locale' => 'en', 'content' => 'Lorem ipsum dolor sit amet']);

        $pagesWithStaticBlocks = Page::with('blocks')->whereHas('blocks', function ($query) {
            $query->where('type', 'static');
        });

        $this->assertEquals(1, $pagesWithStaticBlocks->get()->count());

        Page::addGlobalScope(new TestTranslationScope());
        $this->assertTrue(Page::hasGlobalScope(new TestTranslationScope()));

        Block::addGlobalScope(new TestTranslationScope());
        $this->assertTrue(Block::hasGlobalScope(new TestTranslationScope()));

        $pagesWithStaticBlocks = Page::with('blocks')->whereHas('blocks', function ($query) {
            $query->where('type', 'static');
        });

        $questionMarksCount = substr_count($pagesWithStaticBlocks->toSql(), '?');

        $bindingsCount = count($pagesWithStaticBlocks->getBindings());

        $this->assertEquals($questionMarksCount, $bindingsCount);
    }

    protected function createSchema()
    {
        $this->schema()->create('pages', function ($table) {
            $table->increments('id');
            $table->string('uri');
            $table->timestamps();
        });

        $this->schema()->create('page_translations', function ($table) {
            $table->increments('id');
            $table->integer('page_id');
            $table->string('locale');
            $table->text('content');
            $table->timestamps();
        });

        $this->schema()->create('blocks', function ($table) {
            $table->increments('id');
            $table->integer('page_id');
            $table->string('type');
            $table->timestamps();
        });

        $this->schema()->create('block_translations', function ($table) {
            $table->increments('id');
            $table->integer('block_id');
            $table->string('locale');
            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Helpers...
     */

    /**
     * Get a database connection instance.
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class Page extends Eloquent
{
    protected $table = 'pages';

    protected $guarded = [];

    public function blocks()
    {
        return $this->hasMany('Block', 'page_id');
    }

    public function translations()
    {
        return $this->hasMany('PageTranslation', 'page_id');
    }
}

class PageTranslation extends Eloquent
{
    protected $table = 'page_translations';

    protected $guarded = [];

    public function page()
    {
        return $this->belongsTo('Page', 'page_id');
    }
}

class Block extends Eloquent
{
    protected $table = 'blocks';

    protected $guarded = [];

    public function page()
    {
        return $this->belongsTo('Page', 'page_id');
    }

    public function translations()
    {
        return $this->hasMany('BlockTranslation', 'block_id');
    }
}

class BlockTranslation extends Eloquent
{
    protected $table = 'block_translations';

    protected $guarded = [];

    public function block()
    {
        return $this->belongsTo('Block', 'block_id');
    }
}

class TestTranslationScope implements ScopeInterface
{
    /**
     * This holds the index of the new join binding.
     * @var int
     */
    protected $bindingIndex;

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model   $model
     *
     * @return void
     */
    public function apply(Builder $builder, Eloquent $model)
    {
        $translationTable = $model->translations()->getModel()->getTable();
        $foreignKey = $model->getForeignKey();

        $this->bindingIndex = count($builder->getQuery()->getRawBindings()['join']);

        $builder->leftJoin($translationTable, function ($join) use ($translationTable, $foreignKey, $model) {
            $join->on($translationTable.'.'.$foreignKey, '=', $model->getQualifiedKeyName())
                ->where($translationTable.'.locale', '=', 'en');
        });
    }

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model   $model
     *
     * @return void
     */
    public function remove(Builder $builder, Eloquent $model)
    {
        $query = $builder->getQuery();

        $bindings = $query->getRawBindings()['join'];

        unset($bindings[$this->bindingIndex]);
        $bindings = array_values($bindings);
        $query->setBindings($bindings, 'join');

        $translationTable = $model->translations()->getModel()->getTable();

        $query->joins = collect($query->joins)
            ->reject(function ($join) use ($translationTable) {
                return $join->table == $translationTable;
            })
            ->values()
            ->all();
    }
}
