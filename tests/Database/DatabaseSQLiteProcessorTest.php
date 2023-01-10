<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Query\Processors\SQLiteProcessor;
use PHPUnit\Framework\TestCase;

class DatabaseSQLiteProcessorTest extends TestCase
{
    public function testProcessColumns()
    {
        $processor = new SQLiteProcessor;

        $listing = [['name' => 'id', 'type' => 'integer'], ['name' => 'name', 'type' => 'varchar'], ['name' => 'is_active', 'type' => 'tinyint(1)']];
        $expected = [
            ['name' => 'id', 'type_name' => 'integer', 'type' => 'integer'],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'varchar'],
            ['name' => 'is_active', 'type_name' => 'tinyint', 'type' => 'tinyint(1)'],
        ];

        $this->assertEquals($expected, $processor->processColumns($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumns($listing));
    }
}
