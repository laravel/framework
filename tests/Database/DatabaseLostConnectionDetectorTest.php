<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\LostConnectionDetector;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\TestCase;

class DatabaseLostConnectionDetectorTest extends TestCase
{
    public function testDetectsLostConnectionFromEnglishMessage()
    {
        $detector = new LostConnectionDetector;

        $e = new QueryException('mysql', 'SELECT 1', [], new \Exception('server has gone away'));

        $this->assertTrue($detector->causedByLostConnection($e));
    }

    public function testDetectsLostConnectionFromMysqlErrorCode2002()
    {
        $detector = new LostConnectionDetector;

        $pdo = new PDOException('SQLSTATE[HY000] [2002] Connexion terminée par expiration du délai d\'attente');
        $pdo->errorInfo = ['HY000', 2002, 'Connexion terminée par expiration du délai d\'attente'];

        $e = new QueryException('mysql', 'SELECT 1', [], $pdo);

        $this->assertTrue($detector->causedByLostConnection($e));
    }

    public function testDetectsLostConnectionFromMysqlErrorCode2006()
    {
        $detector = new LostConnectionDetector;

        $pdo = new PDOException('SQLSTATE[HY000] [2006] Le serveur MySQL a disparu');
        $pdo->errorInfo = ['HY000', 2006, 'Le serveur MySQL a disparu'];

        $e = new QueryException('mysql', 'SELECT 1', [], $pdo);

        $this->assertTrue($detector->causedByLostConnection($e));
    }

    public function testDetectsLostConnectionFromMysqlErrorCode2013()
    {
        $detector = new LostConnectionDetector;

        $pdo = new PDOException('SQLSTATE[HY000] [2013] Connexion perdue');
        $pdo->errorInfo = ['HY000', 2013, 'Connexion perdue'];

        $e = new QueryException('mysql', 'SELECT 1', [], $pdo);

        $this->assertTrue($detector->causedByLostConnection($e));
    }

    public function testDoesNotDetectLostConnectionForUnrelatedErrorCode()
    {
        $detector = new LostConnectionDetector;

        $pdo = new PDOException('SQLSTATE[42S02] [1146] Table does not exist');
        $pdo->errorInfo = ['42S02', 1146, 'Table does not exist'];

        $e = new QueryException('mysql', 'SELECT 1', [], $pdo);

        $this->assertFalse($detector->causedByLostConnection($e));
    }

    public function testDoesNotDetectLostConnectionForGenericException()
    {
        $detector = new LostConnectionDetector;

        $e = new \RuntimeException('Something unrelated');

        $this->assertFalse($detector->causedByLostConnection($e));
    }
}
