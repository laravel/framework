<?php

namespace Illuminate\Tests\Integration\Database\Postgres;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_pgsql')]
class FulltextTest extends PostgresTestCase
{
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
            ['title' => 'PostgreSQL Tutorial', 'body' => 'DBMS stands for DataBase ...'],
            ['title' => 'How To Use PostgreSQL Well', 'body' => 'After you went through a ...'],
            ['title' => 'Optimizing PostgreSQL', 'body' => 'In this tutorial, we show ...'],
            ['title' => '1001 PostgreSQL Tricks', 'body' => '1. Never run mysqld as root. 2. ...'],
            ['title' => 'PostgreSQL vs. YourSQL', 'body' => 'In the following database comparison ...'],
            ['title' => 'PostgreSQL Security', 'body' => 'When configured properly, PostgreSQL ...'],
        ]);
    }

    public function testWhereFulltext()
    {
        $articles = DB::table('articles')->whereFullText(['title', 'body'], 'database')->orderBy('id')->get();

        $this->assertCount(2, $articles);
        $this->assertSame('PostgreSQL Tutorial', $articles[0]->title);
        $this->assertSame('PostgreSQL vs. YourSQL', $articles[1]->title);
    }

    #[RequiresDatabase('pgsql', '>=11.0')]
    public function testWhereFulltextWithWebsearch()
    {
        $articles = DB::table('articles')->whereFullText(['title', 'body'], '+PostgreSQL -YourSQL', ['mode' => 'websearch'])->get();

        $this->assertCount(5, $articles);
    }

    public function testWhereFulltextWithPlain()
    {
        $articles = DB::table('articles')->whereFullText(['title', 'body'], 'PostgreSQL tutorial', ['mode' => 'plain'])->get();

        $this->assertCount(2, $articles);
    }

    public function testWhereFulltextWithPhrase()
    {
        $articles = DB::table('articles')->whereFullText(['title', 'body'], 'PostgreSQL tutorial', ['mode' => 'phrase'])->get();

        $this->assertCount(1, $articles);
    }
}
