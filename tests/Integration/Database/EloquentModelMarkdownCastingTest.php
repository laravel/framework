<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Casts\Markdown;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

class EloquentModelMarkdownCastingTest extends TestCase
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

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('markdown_casting_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('content');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('markdown_casting_table');
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

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Tests..
     */
    public function testInit()
    {
        $model = \Tests\Feature\MarkdownCustomCasts::firstOrCreate([
            'content' => '# Taylor <b>Otwell</b>',
        ]);

        $this->assertSame("<h1>Taylor <b>Otwell</b></h1>\n", $model->content);
    }

    public function testInline()
    {
        $model = MarkdownCustomCasts::firstOrCreate([
            'content' => '**Taylor Otwell**',
        ]);

        $model->mergeCasts([
            'content' => 'markdown:inline',
        ]);

        $this->assertSame("<strong>Taylor Otwell</strong>\n", $model->content);
    }

    public function testHtmlInputStrip()
    {
        $model = MarkdownCustomCasts::firstOrCreate([
            'content' => '# Taylor <b>Otwell</b>',
        ]);

        $model->mergeCasts([
            'content' => 'markdown:html_input=strip',
        ]);

        $this->assertSame("<h1>Taylor Otwell</h1>\n", $model->content);
    }

    public function testCommonMarkNestedOption()
    {
        $model = MarkdownCustomCasts::firstOrCreate([
            'content' => '# Taylor *Otwell*',
        ]);

        $model->mergeCasts([
            'content' => Markdown::class.':commonmark.enable_em=false',
        ]);

        $this->assertSame("<h1>Taylor *Otwell*</h1>\n", $model->content);
    }

    public function testCastableClass()
    {
        $model = MarkdownCustomCasts::firstOrCreate([
            'content' => '# Taylor <b>Otwell</b>',
        ]);

        $model->mergeCasts([
            'content' => Markdown::class.':html_input=strip',
        ]);

        $this->assertSame("<h1>Taylor Otwell</h1>\n", $model->content);
    }
}

/**
 * Eloquent Models...
 */
class MarkdownCustomCasts extends Eloquent
{
    /**
     * @var string
     */
    protected $table = 'markdown_casting_table';

    /**
     * @var string[]
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'content' => 'markdown',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;
}
