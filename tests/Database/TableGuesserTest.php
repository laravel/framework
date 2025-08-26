<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Console\Migrations\TableGuesser;
use PHPUnit\Framework\TestCase;

class TableGuesserTest extends TestCase
{
    public function testMigrationIsProperlyParsed()
    {
        [$table, $create, $drop] = TableGuesser::guess('create_users_table');
        $this->assertSame('users', $table);
        $this->assertTrue($create);
        $this->assertFalse($drop);

        [$table, $create, $drop] = TableGuesser::guess('add_status_column_to_users_table');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
        $this->assertFalse($drop);

        [$table, $create, $drop] = TableGuesser::guess('add_is_sent_to_crm_column_to_users_table');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
        $this->assertFalse($drop);

        [$table, $create, $drop] = TableGuesser::guess('change_status_column_in_users_table');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
        $this->assertFalse($drop);

        [$table, $create, $drop] = TableGuesser::guess('drop_status_column_from_users_table');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
        $this->assertFalse($drop);

        [$table, $create, $drop] = TableGuesser::guess('drop_users_table');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
        $this->assertTrue($drop);
    }

    public function testMigrationIsProperlyParsedWithoutTableSuffix()
    {
        [$table, $create, $drop] = TableGuesser::guess('create_users');
        $this->assertSame('users', $table);
        $this->assertTrue($create);
        $this->assertFalse($drop);

        [$table, $create, $drop] = TableGuesser::guess('add_status_column_to_users');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
        $this->assertFalse($drop);

        [$table, $create, $drop] = TableGuesser::guess('add_is_sent_to_crm_column_column_to_users');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
        $this->assertFalse($drop);

        [$table, $create, $drop] = TableGuesser::guess('change_status_column_in_users');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
        $this->assertFalse($drop);

        [$table, $create, $drop] = TableGuesser::guess('drop_status_column_from_users');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
        $this->assertFalse($drop);

        [$table, $create, $drop] = TableGuesser::guess('drop_users');
        $this->assertSame('users', $table);
        $this->assertFalse($create);
        $this->assertTrue($drop);
    }
}
