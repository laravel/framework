<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('sqlite')]
class FulltextTest extends DatabaseTestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'conn1');

        $app['config']->set('database.connections.conn1', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function afterRefreshingDatabase()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id('id');
            $table->string('title', 200);
            $table->text('body');
            $table->fulltext(['title', 'body']);
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('articles');
    }

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('articles')->insert([
            ['title' => 'SQLite Tutorial', 'body' => 'DBMS stands for DataBase ...'],
            ['title' => 'How To Use SQLite Well', 'body' => 'After you went through a ...'],
            ['title' => 'Optimizing SQLite', 'body' => 'In this tutorial, we show ...'],
            ['title' => '1001 SQLite Tricks', 'body' => '1. sqlite3: Use this command. 2. ...'],
            ['title' => 'SQLite vs. YourSQL', 'body' => 'Databases: In the following database comparison ...'],
            ['title' => 'SQLite Security', 'body' => 'When configured properly, SQLite ...'],
        ]);
    }

    public function testWhereFulltext()
    {
        $articles = DB::table('articles')
            ->select('title')
            ->whereFullText(['title', 'body'], 'database')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $articles);
        $this->assertSame('SQLite Tutorial', $articles[0]->title);
        $this->assertSame('SQLite vs. YourSQL', $articles[1]->title);
    }

    public function testWhereFulltextMultiple()
    {
        $articles = DB::table('articles')
            ->select('title')
            ->whereFullText(['title', 'body'], 'sqlite database')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $articles);
        $this->assertSame('SQLite Tutorial', $articles[0]->title);
        $this->assertSame('SQLite vs. YourSQL', $articles[1]->title);
    }

    public function testWhereFulltextPrefix()
    {
        $articles = DB::table('articles')
            ->select(['title'])
            ->whereFullText(['title', 'body'], 'sql* database*')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $articles);
        $this->assertSame('SQLite Tutorial', $articles[0]->title);
        $this->assertSame('SQLite vs. YourSQL', $articles[1]->title);
    }

    public function testWhereFulltextPhrase()
    {
        $articles = DB::table('articles')
            ->select(['title'])
            ->whereFullText(['title', 'body'], '"use sqlite"')
            ->orderBy('id')
            ->get();

        $this->assertCount(1, $articles);
        $this->assertSame('How To Use SQLite Well', $articles[0]->title);
    }

    public function testWhereFulltextBoolean()
    {
        $articles = DB::table('articles')
            ->select(['title'])
            ->whereFullText(['title', 'body'], 'sqlite NOT database*')
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $articles);
        $this->assertSame('How To Use SQLite Well', $articles[0]->title);
    }
}
