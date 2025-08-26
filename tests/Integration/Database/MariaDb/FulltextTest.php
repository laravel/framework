<?php

namespace Illuminate\Tests\Integration\Database\MariaDb;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_mysql')]
class FulltextTest extends MariaDbTestCase
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
            ['title' => 'MariaDB Tutorial', 'body' => 'DBMS stands for DataBase ...'],
            ['title' => 'How To Use MariaDB Well', 'body' => 'After you went through a ...'],
            ['title' => 'Optimizing MariaDB', 'body' => 'In this tutorial, we show ...'],
            ['title' => '1001 MariaDB Tricks', 'body' => '1. Never run mariadbd as root. 2. ...'],
            ['title' => 'MariaDB vs. YourSQL', 'body' => 'In the following database comparison ...'],
            ['title' => 'MariaDB Security', 'body' => 'When configured properly, MariaDB ...'],
        ]);
    }

    /** @link https://mariadb.com/kb/en/full-text-index-overview/#in-natural-language-mode */
    public function testWhereFulltext()
    {
        $articles = DB::table('articles')->whereFullText(['title', 'body'], 'database')->get();

        $this->assertCount(2, $articles);
        $this->assertSame('MariaDB Tutorial', $articles[0]->title);
        $this->assertSame('MariaDB vs. YourSQL', $articles[1]->title);
    }

    /** @link https://mariadb.com/kb/en/full-text-index-overview/#in-boolean-mode */
    public function testWhereFulltextWithBooleanMode()
    {
        $articles = DB::table('articles')->whereFullText(['title', 'body'], '+MariaDB -YourSQL', ['mode' => 'boolean'])->get();

        $this->assertCount(5, $articles);
    }

    /** @link https://mariadb.com/kb/en/full-text-index-overview/#with-query-expansion */
    public function testWhereFulltextWithExpandedQuery()
    {
        $articles = DB::table('articles')->whereFullText(['title', 'body'], 'database', ['expanded' => true])->get();

        $this->assertCount(6, $articles);
    }
}
