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
            ['name' => 'id', 'type_name' => 'bigserial', 'length' => null, 'total' => 64, 'places' => null, 'precision' => null],
            ['name' => 'name', 'type_name' => 'character varying', 'length' => 100, 'total' => null, 'places' => null, 'precision' => null],
            ['name' => 'balance', 'type_name' => 'numeric', 'length' => null, 'total' => 8, 'places' => 0, 'precision' => null],
            ['name' => 'birth_date', 'type_name' => 'timestamp without time zone', 'length' => null, 'total' => null, 'places' => null, 'precision' => 6],
        ];
        $expected = [
            ['name' => 'id', 'type_name' => 'bigserial', 'type' => 'bigserial'],
            ['name' => 'name', 'type_name' => 'character varying', 'type' => 'character varying(100)'],
            ['name' => 'balance', 'type_name' => 'numeric', 'type' => 'numeric(8,0)'],
            ['name' => 'birth_date', 'type_name' => 'timestamp without time zone', 'type' => 'timestamp(6) without time zone'],
        ];

        $this->assertEquals($expected, $processor->processColumns($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumns($listing));
    }
}
