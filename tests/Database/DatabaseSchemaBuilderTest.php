<?php

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\Grammar;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaBuilderTest extends TestCase
{
    public function testCreateDatabase()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = Mockery::mock(Grammar::class);
        $grammar->expects('compileCreateDatabase')->andReturn('sql');
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('statement')->with('sql')->andReturnTrue();
        $builder = new Builder($connection);

        $this->assertTrue($builder->createDatabase('foo'));
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = Mockery::mock(Grammar::class);
        $grammar->expects('compileDropDatabaseIfExists')->andReturn('sql');
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('statement')->with('sql')->andReturnTrue();
        $builder = new Builder($connection);

        $this->assertTrue($builder->dropDatabaseIfExists('foo'));
    }

    public function testHasTableCorrectlyCallsGrammar()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = Mockery::mock(Grammar::class);
        $processor = Mockery::mock(Processor::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $builder = new Builder($connection);
        $grammar->expects('compileTableExists');
        $grammar->expects('compileTables')->andReturn('sql');
        $processor->expects('processTables')->andReturn([['name' => 'prefix_table']]);
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'prefix_table']]);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testTableHasColumns()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = Mockery::mock(Grammar::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $builder = Mockery::mock(Builder::class.'[getColumnListing]', [$connection]);
        $builder->expects('getColumnListing')->with('users')->times(2)->andReturn(['id', 'firstname']);

        $this->assertTrue($builder->hasColumns('users', ['id', 'firstname']));
        $this->assertFalse($builder->hasColumns('users', ['id', 'address']));
    }

    public function testWhenTableHasForeignKey()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('getSchemaGrammar')->andReturn(Mockery::mock(Grammar::class));
        $builder = Mockery::mock(Builder::class.'[hasForeignKey,table]', [$connection]);
        $builder->expects('hasForeignKey')->with('posts', 'posts_user_id_foreign')->andReturnTrue();
        $builder->expects('hasForeignKey')->with('posts', ['missing_id'])->andReturnFalse();
        $builder->expects('table')->with('posts', Mockery::type(Closure::class))
            ->andReturnUsing(fn ($table, $callback) => $callback(Mockery::mock(Blueprint::class)));

        $ran = [];

        $builder->whenTableHasForeignKey('posts', 'posts_user_id_foreign', function (Blueprint $table) use (&$ran) {
            $ran[] = 'existing';
        });

        $builder->whenTableHasForeignKey('posts', ['missing_id'], function (Blueprint $table) use (&$ran) {
            $ran[] = 'missing';
        });

        $this->assertSame(['existing'], $ran);
    }

    public function testWhenTableDoesntHaveForeignKey()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('getSchemaGrammar')->andReturn(Mockery::mock(Grammar::class));
        $builder = Mockery::mock(Builder::class.'[hasForeignKey,table]', [$connection]);
        $builder->expects('hasForeignKey')->with('posts', ['missing_id'])->andReturnFalse();
        $builder->expects('hasForeignKey')->with('posts', 'posts_user_id_foreign')->andReturnTrue();
        $builder->expects('table')->with('posts', Mockery::type(Closure::class))
            ->andReturnUsing(fn ($table, $callback) => $callback(Mockery::mock(Blueprint::class)));

        $ran = [];

        $builder->whenTableDoesntHaveForeignKey('posts', ['missing_id'], function (Blueprint $table) use (&$ran) {
            $ran[] = 'missing';
        });

        $builder->whenTableDoesntHaveForeignKey('posts', 'posts_user_id_foreign', function (Blueprint $table) use (&$ran) {
            $ran[] = 'existing';
        });

        $this->assertSame(['missing'], $ran);
    }

    public function testGetColumnTypeAddsPrefix()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = Mockery::mock(Grammar::class);
        $processor = Mockery::mock(Processor::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $processor->expects('processColumns')->andReturn([['name' => 'id', 'type_name' => 'integer']]);
        $builder = new Builder($connection);
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $grammar->expects('compileColumns')->with(null, 'prefix_users')->andReturn('sql');
        $connection->expects('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'id', 'type_name' => 'integer']]);

        $this->assertSame('integer', $builder->getColumnType('users', 'id'));
    }
}
