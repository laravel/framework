<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\MySqlConnection;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class DatabaseMySqlConnectionTest extends TestCase
{
    public function testBindFloatValue()
    {
        $connection = new MySqlConnection(
            function () {
                throw new \UnexpectedValueException('Not expecting to really connect to database');
            }
        );

        $statement = $this->createMock(PDOStatement::class);
        $statement
            ->expects($this->at(0))
            ->method('bindValue')
            ->with(1, 0.5, PDO::PARAM_STR);

        $connection->bindValues($statement, [0 => 0.5]);
    }

    public function testBindStringValue()
    {
        $connection = new MySqlConnection(
            function () {
                throw new \UnexpectedValueException('Not expecting to really connect to database');
            }
        );

        $statement = $this->createMock(PDOStatement::class);
        $statement
            ->expects($this->at(0))
            ->method('bindValue')
            ->with(1, 'test', PDO::PARAM_STR);

        $connection->bindValues($statement, [0 => 'test']);
    }

    public function testBindIntegerValue()
    {
        $connection = new MySqlConnection(
            function () {
                throw new \UnexpectedValueException('Not expecting to really connect to database');
            }
        );

        $statement = $this->createMock(PDOStatement::class);
        $statement
            ->expects($this->at(0))
            ->method('bindValue')
            ->with(1, 27, PDO::PARAM_INT);

        $connection->bindValues($statement, [0 => 27]);
    }
}
