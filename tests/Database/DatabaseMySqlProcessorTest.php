<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Query\Processors\MySqlProcessor;
use PHPUnit\Framework\TestCase;

class DatabaseMySqlProcessorTest extends TestCase
{
    public function testProcessColumns()
    {
        $processor = new MySqlProcessor;
        $listing = [
            ['name' => 'id', 'type_name' => 'bigint', 'type' => 'bigint'],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'varchar(100)'],
            ['name' => 'email', 'type_name' => 'varchar', 'type' => 'varchar(100)'],
        ];
        $expected = [
            ['name' => 'id', 'type_name' => 'bigint', 'type' => 'bigint'],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'varchar(100)'],
            ['name' => 'email', 'type_name' => 'varchar', 'type' => 'varchar(100)'],
        ];
        $this->assertEquals($expected, $processor->processColumns($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumns($listing));
    }
}
