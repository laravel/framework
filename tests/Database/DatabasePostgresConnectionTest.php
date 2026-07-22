<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\PostgresConnection;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabasePostgresConnectionTest extends TestCase
{
    public function testBooleanBindingsAreStringifiedWhenUsingEmulatedPrepares()
    {
        $connection = new PostgresConnection(new DatabasePostgresConnectionTestMockPDO, 'database', '', [
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ]);

        $this->assertSame(['true', 'false'], $connection->prepareBindings([true, false]));
    }

    public function testBooleanBindingsAreStringifiedWhenUsingTruthyEmulatedPreparesOption()
    {
        $connection = new PostgresConnection(new DatabasePostgresConnectionTestMockPDO, 'database', '', [
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => 1,
            ],
        ]);

        $this->assertSame(['true', 'false'], $connection->prepareBindings([true, false]));
    }

    public function testBooleanBindingsUseDefaultIntegerConversionWhenNotUsingEmulatedPrepares()
    {
        $connection = new PostgresConnection(new DatabasePostgresConnectionTestMockPDO, 'database', '', [
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ]);

        $this->assertSame([1, 0], $connection->prepareBindings([true, false]));
    }

    public function testBooleanBindingsUseReadPdoConfigWhenReadConnectionIsActive()
    {
        $connection = new PostgresConnection(new DatabasePostgresConnectionTestMockPDO, 'database', '', [
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ]);
        $connection->setReadPdoConfig([
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ]);
        $connection->setReadWriteType('read');

        $this->assertSame(['true', 'false'], $connection->prepareBindings([true, false]));
    }

    public function testBooleanBindingsUseDirectPdoConfigWhenDirectConnectionIsActive()
    {
        $connection = new PostgresConnection(new DatabasePostgresConnectionTestMockPDO, 'database', '', [
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ]);
        $connection->setDirectPdoConfig([
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ]);
        $connection->setReadWriteType('direct');

        $this->assertSame([1, 0], $connection->prepareBindings([true, false]));
    }
}

class DatabasePostgresConnectionTestMockPDO extends PDO
{
    public function __construct()
    {
        //
    }
}
