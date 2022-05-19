<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connectors\Connector;
use PHPUnit\Framework\TestCase;

class DatabaseConnectorTest extends TestCase
{
    public function testOptionResolution()
    {
        $connector = new Connector;
        $connector->setDefaultOptions([0 => 'foo', 1 => 'bar']);
        $this->assertEquals([0 => 'baz', 1 => 'bar', 2 => 'boom'], $connector->getOptions(['options' => [0 => 'baz', 2 => 'boom']]));
    }
}
