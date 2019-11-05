<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Console\Migrations\TableGuesser;
use PHPUnit\Framework\TestCase;

class TableGuesserTest extends TestCase
{
    public function testMigrationIsProperlyParsed()
    {
        [$table, $create] = TableGuesser::guess('create_users_table');
        $this->assertSame('users', $table);
        $this->assertTrue($create);

        [$table, $create] = TableGuesser::guess('add_status_column_to_users_table');
        $this->assertSame('users', $table);
        $this->assertFalse($create);

        [$table, $create] = TableGuesser::guess('change_status_column_in_users_table');
        $this->assertSame('users', $table);
        $this->assertFalse($create);

        [$table, $create] = TableGuesser::guess('drop_status_column_from_users_table');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
    }

    public function testMigrationIsProperlyParsedWithoutTableSuffix()
    {
        [$table, $create] = TableGuesser::guess('create_users');
        $this->assertSame('users', $table);
        $this->assertTrue($create);

        [$table, $create] = TableGuesser::guess('add_status_column_to_users');
        $this->assertSame('users', $table);
        $this->assertFalse($create);

        [$table, $create] = TableGuesser::guess('change_status_column_in_users');
        $this->assertSame('users', $table);
        $this->assertFalse($create);

        [$table, $create] = TableGuesser::guess('drop_status_column_from_users');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
    }
}
