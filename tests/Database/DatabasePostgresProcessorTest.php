<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Query\Processors\PostgresProcessor;
use PHPUnit\Framework\TestCase;

class DatabasePostgresProcessorTest extends TestCase
{
    public function testProcessColumnListing()
    {
        $processor = new PostgresProcessor;

        $listing = [['column_name' => 'id'], ['column_name' => 'name'], ['column_name' => 'email']];
        $expected = ['id', 'name', 'email'];

        $this->assertEquals($expected, $processor->processColumnListing($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumnListing($listing));
    }
}
