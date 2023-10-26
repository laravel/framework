<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Query\Processors\PostgresProcessor;
use PHPUnit\Framework\TestCase;

class DatabasePostgresProcessorTest extends TestCase
{
    public function testProcessColumns()
    {
        $processor = new PostgresProcessor;

        $listing = [
            ['name' => 'id', 'type_name' => 'int4', 'type' => 'integer', 'collation' => '', 'nullable' => true, 'default' => "nextval('employee_id_seq'::regclass)", 'comment' => ''],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'character varying(100)', 'collation' => 'collate', 'nullable' => false, 'default' => '', 'comment' => 'foo'],
            ['name' => 'balance', 'type_name' => 'numeric', 'type' => 'numeric(8,2)', 'collation' => '', 'nullable' => true, 'default' => '4', 'comment' => 'NULL'],
            ['name' => 'birth_date', 'type_name' => 'timestamp', 'type' => 'timestamp(6) without time zone', 'collation' => '', 'nullable' => false, 'default' => '', 'comment' => ''],
        ];
        $expected = [
            ['name' => 'id', 'type_name' => 'int4', 'type' => 'integer', 'collation' => '', 'nullable' => true, 'default' => null, 'auto_increment' => true, 'comment' => ''],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'character varying(100)', 'collation' => 'collate', 'nullable' => false, 'default' => '', 'auto_increment' => false, 'comment' => 'foo'],
            ['name' => 'balance', 'type_name' => 'numeric', 'type' => 'numeric(8,2)', 'collation' => '', 'nullable' => true, 'default' => '4', 'auto_increment' => false, 'comment' => 'NULL'],
            ['name' => 'birth_date', 'type_name' => 'timestamp', 'type' => 'timestamp(6) without time zone', 'collation' => '', 'nullable' => false, 'default' => '', 'auto_increment' => false, 'comment' => ''],
        ];

        $this->assertEquals($expected, $processor->processColumns($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumns($listing));
    }
}
