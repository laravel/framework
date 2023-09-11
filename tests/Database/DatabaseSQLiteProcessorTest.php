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
            ['name' => 'id', 'type' => 'INTEGER', 'notnull' => '1', 'dflt_value' => '', 'pk' => '1'],
            ['name' => 'name', 'type' => 'varchar', 'notnull' => '0', 'dflt_value' => 'foo', 'pk' => '0'],
            ['name' => 'is_active', 'type' => 'tinyint(1)', 'notnull' => '1', 'dflt_value' => '1', 'pk' => '0']
        ];
        $expected = [
            ['name' => 'id', 'type_name' => 'integer', 'type' => 'integer', 'collation' => null, 'nullable' => false, 'default' => '', 'auto_increment' => true, 'comment' => null],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'varchar', 'collation' => null, 'nullable' => true, 'default' => 'foo', 'auto_increment' => false, 'comment' => null],
            ['name' => 'is_active', 'type_name' => 'tinyint', 'type' => 'tinyint(1)', 'collation' => null, 'nullable' => false, 'default' => '1', 'auto_increment' => false, 'comment' => null],
        ];

        $this->assertEquals($expected, $processor->processColumns($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumns($listing));
    }
}
