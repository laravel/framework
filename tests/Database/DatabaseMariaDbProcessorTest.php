<?php

namespace Database;

use Illuminate\Database\Query\Processors\MariaDbProcessor;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbProcessorTest extends TestCase
{
    public function testProcessColumns()
    {
        $processor = new MariaDbProcessor;
        $listing = [
            ['name' => 'id', 'type_name' => 'bigint', 'type' => 'bigint', 'collation' => 'collate', 'nullable' => 'YES', 'default' => '', 'extra' => 'auto_increment', 'comment' => 'bar', 'expression' => null],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'varchar(100)', 'collation' => 'collate', 'nullable' => 'NO', 'default' => 'foo', 'extra' => '', 'comment' => '', 'expression' => null],
            ['name' => 'email', 'type_name' => 'varchar', 'type' => 'varchar(100)', 'collation' => 'collate', 'nullable' => 'YES', 'default' => 'NULL', 'extra' => 'on update CURRENT_TIMESTAMP', 'comment' => 'NULL', 'expression' => null],
        ];
        $expected = [
            ['name' => 'id', 'type_name' => 'bigint', 'type' => 'bigint', 'collation' => 'collate', 'nullable' => true, 'default' => '', 'auto_increment' => true, 'comment' => 'bar', 'generation' => null],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'varchar(100)', 'collation' => 'collate', 'nullable' => false, 'default' => 'foo', 'auto_increment' => false, 'comment' => '', 'generation' => null],
            ['name' => 'email', 'type_name' => 'varchar', 'type' => 'varchar(100)', 'collation' => 'collate', 'nullable' => true, 'default' => 'NULL', 'auto_increment' => false, 'comment' => 'NULL', 'generation' => null],
        ];
        $this->assertEquals($expected, $processor->processColumns($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumns($listing));
    }
}
