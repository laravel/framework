<?php

namespace Database;

use Illuminate\Database\LostConnectionDetector;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseConnectionLostTest extends TestCase
{
    #[DataProvider('shouldBeLostProvider')]
    public function test_lost_connection_detector_matches_exception_message($message)
    {
        $detector = new LostConnectionDetector;
        $this->assertTrue($detector->causedByLostConnection(new PDOException($message)));
    }

    public static function shouldBeLostProvider(): array
    {
        return [
            ['SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Try again'],
            ["SQLSTATE[08006] [7] connection to server at \"example.database.com\" (10.0.1.7), port 5432 failed: Connection refused\nIs the server running on that host and accepting TCP/IP connections? (Connection: pgsql, Host: example.database.com, Port: 5432, Database: forge, SQL: select * from \"cache\" where \"key\" in (illuminate:queue:restart))"],
            ['SQLSTATE[HY000]: General error: 2006 MySQL server has gone away'],
            ['SQLSTATE[08S01]: [Microsoft][ODBC Driver 17 for SQL Server]TCP Provider: Error code 0x68'],
            ['SQLSTATE[08S02]: [Microsoft][ODBC Driver 13 for SQL Server]SMux Provider: Physical connection is not usable [xFFFFFFFF]'],
        ];
    }
}
