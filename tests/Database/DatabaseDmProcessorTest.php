<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Query\Processors\DmProcessor;
use PHPUnit\Framework\TestCase;

class DatabaseDmProcessorTest extends TestCase
{
    public function testProcessColumns()
    {
        $processor = new DmProcessor;
        $listing = [
            ['NAME' => 'id', 'TYPE_NAME' => 'bigint', 'LENGTH' => 8,'NULLABLE' => 'Y', 'DEFVAL' => '', 'AUTO_INCREMENT' => true, 'COL_COMMENT' => 'bar', 'VIR_COL' => 1],
            ['NAME' => 'name', 'TYPE_NAME' => 'varchar', 'LENGTH' => 50, 'NULLABLE' => 'N', 'DEFVAL' => 'foo', 'AUTO_INCREMENT' => false, 'COL_COMMENT' => '', 'VIR_COL' => null],
            ['NAME' => 'email', 'TYPE_NAME' => 'varchar', 'LENGTH' => 100, 'NULLABLE' => 'Y', 'DEFVAL' => 'NULL', 'AUTO_INCREMENT' => false,'extra' => 'on update CURRENT_TIMESTAMP', 'COL_COMMENT' => 'NULL', 'VIR_COL' => null],
        ];
        $expected = [
            ['name' => 'id', 'type' => 'bigint', 'length' => 8,'nullable' => true, 'default' => '', 'auto_increment' => true, 'comment' => 'bar', 'virtual' => true],
            ['name' => 'name', 'type' => 'varchar', 'length' => 50,'nullable' => false, 'default' => 'foo', 'auto_increment' => false, 'comment' => '','virtual' => false],
            ['name' => 'email', 'type' => 'varchar', 'length' => 100,'nullable' => true, 'default' => 'NULL', 'auto_increment' => false, 'comment' => 'NULL','virtual' => false],
        ];
        $this->assertEquals($expected, $processor->processColumns($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumns($listing));
    }
}
