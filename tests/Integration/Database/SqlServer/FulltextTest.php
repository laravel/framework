<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_sqlsrv')]
class FulltextTest extends SqlServerTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id('id');
            $table->string('title', 200);
            $table->text('body');
            $table->primary('id', 'pk_articles_id');
            $table->fulltext(['title', 'body'])->pkindex('pk_articles_id');
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
            ['title' => 'SQLServer Tutorial', 'body' => 'DBMS stands for DataBase ...'],
            ['title' => 'How To Use SQLServer Well', 'body' => 'After you went through a ...'],
            ['title' => 'Optimizing SQLServer', 'body' => 'In this tutorial, we show ...'],
            ['title' => '1001 SQLServer Tricks', 'body' => '1. Never run mysqld as root. 2. ...'],
            ['title' => 'SQLServer vs. YourSQL', 'body' => 'In the following database comparison ...'],
            ['title' => 'SQLServer Security', 'body' => 'When configured properly, SQLServer ...'],
        ]);
    }

    public function testWhereFulltext()
    {
        $articles = DB::table('articles')->whereFullText(['title', 'body'], 'database')->orderBy('id')->get();

        $this->assertCount(2, $articles);
        $this->assertSame('SQLServer Tutorial', $articles[0]->title);
        $this->assertSame('SQLServer vs. YourSQL', $articles[1]->title);
    }

    public function testWhereFulltextExpanded()
    {
        $articles = DB::table('articles')->whereFullText(['title', 'body'], 'database', ['expanded' => true])->orderBy('id')->get();

        $this->assertCount(2, $articles);
        $this->assertSame('SQLServer Tutorial', $articles[0]->title);
        $this->assertSame('SQLServer vs. YourSQL', $articles[1]->title);
    }
}
