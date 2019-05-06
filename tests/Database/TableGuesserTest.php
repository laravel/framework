<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Console\Migrations\TableGuesser;

class TableGuesserTest extends TestCase
{
    public function test_migration_is_properly_parsed()
    {
        [$table, $create] = TableGuesser::guess('create_users_table');
        $this->assertEquals('users', $table);
        $this->assertTrue($create);

        [$table, $create] = TableGuesser::guess('add_status_column_to_users_table');
        $this->assertEquals('users', $table);
        $this->assertFalse($create);

        [$table, $create] = TableGuesser::guess('change_status_column_in_users_table');
        $this->assertEquals('users', $table);
        $this->assertFalse($create);

        [$table, $create] = TableGuesser::guess('drop_status_column_from_users_table');
        $this->assertEquals('users', $table);
        $this->assertFalse($create);
    }

    public function test_migration_is_properly_parsed_without_table_suffix()
    {
        [$table, $create] = TableGuesser::guess('create_users');
        $this->assertEquals('users', $table);
        $this->assertTrue($create);

        [$table, $create] = TableGuesser::guess('add_status_column_to_users');
        $this->assertEquals('users', $table);
        $this->assertFalse($create);

        [$table, $create] = TableGuesser::guess('change_status_column_in_users');
        $this->assertEquals('users', $table);
        $this->assertFalse($create);

        [$table, $create] = TableGuesser::guess('drop_status_column_from_users');
        $this->assertEquals('users', $table);
        $this->assertFalse($create);
    }
}
