<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar as SqlServerSchemaGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar as PostgresSchemaGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlSchemaGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar as SQLiteSchemaGrammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar as MySqlQueryGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar as SQLiteQueryGrammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar as SqlServerQueryGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar as PostgresQueryGrammar;

class DatabaseGrammarTest extends TestCase
{
    public function testMacroableFunctionsAreUniquePerGrammarQueryClass()
    {
        // compileReplace macro.
        MySqlQueryGrammar::macro('compileReplace', function () {
            return MySqlQueryGrammar::class;
        });

        SQLiteQueryGrammar::macro('compileReplace', function () {
            return SQLiteQueryGrammar::class;
        });

        PostgresQueryGrammar::macro('compileReplace', function () {
            return PostgresQueryGrammar::class;
        });

        SqlServerQueryGrammar::macro('compileReplace', function () {
            return SqlServerQueryGrammar::class;
        });

        $this->assertSame(MySqlQueryGrammar::class, (new MySqlQueryGrammar())->compileReplace());
        $this->assertSame(SqlServerQueryGrammar::class, (new SqlServerQueryGrammar())->compileReplace());
        $this->assertSame(PostgresQueryGrammar::class, (new PostgresQueryGrammar())->compileReplace());
        $this->assertSame(SQLiteQueryGrammar::class, (new SQLiteQueryGrammar())->compileReplace());
    }

    public function testMacroableFunctionsAreUniquePerGrammarSchemaClass()
    {
        // compileReplace macro.
        MySqlSchemaGrammar::macro('compileReplace', function () {
            return MySqlSchemaGrammar::class;
        });

        SQLiteSchemaGrammar::macro('compileReplace', function () {
            return SQLiteSchemaGrammar::class;
        });

        PostgresSchemaGrammar::macro('compileReplace', function () {
            return PostgresSchemaGrammar::class;
        });

        SqlServerSchemaGrammar::macro('compileReplace', function () {
            return SqlServerSchemaGrammar::class;
        });

        $this->assertSame(MySqlSchemaGrammar::class, (new MySqlSchemaGrammar())->compileReplace());
        $this->assertSame(SqlServerSchemaGrammar::class, (new SqlServerSchemaGrammar())->compileReplace());
        $this->assertSame(PostgresSchemaGrammar::class, (new PostgresSchemaGrammar())->compileReplace());
        $this->assertSame(SQLiteSchemaGrammar::class, (new SQLiteSchemaGrammar())->compileReplace());
    }
}
