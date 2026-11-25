<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\PostgresConnection;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    public function testHasCommitIdWithWaitDoesNotConnectToTheWriteConnection()
    {
        $statement = $this->createStub(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([(object) ['status' => 'success']]);

        $readPdo = $this->createStub(PDO::class);
        $readPdo->method('getAttribute')->willReturn('19.0');
        $readPdo->method('quote')->willReturnCallback(fn ($value) => "'{$value}'");
        $readPdo->method('prepare')->willReturn($statement);

        $connection = new PostgresConnection(fn () => throw new RuntimeException('Connected to the write connection.'));
        $connection->setReadPdo($readPdo);

        $this->assertTrue($connection->hasCommitId('0/16B3748', wait: 1));
    }
}

class DatabasePostgresConnectionTestMockPDO extends PDO
{
    public function __construct()
    {
        //
    }
}
