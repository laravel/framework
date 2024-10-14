<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Query\Processors\SQLiteProcessor;
use PHPUnit\Framework\TestCase;

class DatabaseSQLiteProcessorTest extends TestCase
{
    public function testProcessColumns()
    {
        $processor = new SQLiteProcessor;

        $listing = [
            ['name' => 'id', 'type' => 'INTEGER', 'nullable' => '0', 'default' => '', 'primary' => '1', 'extra' => 1],
            ['name' => 'name', 'type' => 'varchar', 'nullable' => '1', 'default' => 'foo', 'primary' => '0', 'extra' => 1],
            ['name' => 'is_active', 'type' => 'tinyint(1)', 'nullable' => '0', 'default' => '1', 'primary' => '0', 'extra' => 1],
            ['name' => 'with/slash', 'type' => 'tinyint(1)', 'nullable' => '0', 'default' => '1', 'primary' => '0', 'extra' => 1],
        ];
        $expected = [
            ['name' => 'id', 'type_name' => 'integer', 'type' => 'integer', 'collation' => null, 'nullable' => false, 'default' => '', 'auto_increment' => true, 'comment' => null, 'generation' => null],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'varchar', 'collation' => null, 'nullable' => true, 'default' => 'foo', 'auto_increment' => false, 'comment' => null, 'generation' => null],
            ['name' => 'is_active', 'type_name' => 'tinyint', 'type' => 'tinyint(1)', 'collation' => null, 'nullable' => false, 'default' => '1', 'auto_increment' => false, 'comment' => null, 'generation' => null],
            ['name' => 'with/slash', 'type_name' => 'tinyint', 'type' => 'tinyint(1)', 'collation' => null, 'nullable' => false, 'default' => '1', 'auto_increment' => false, 'comment' => null, 'generation' => null],
        ];

        $this->assertEquals($expected, $processor->processColumns($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumns($listing));
    }
}
