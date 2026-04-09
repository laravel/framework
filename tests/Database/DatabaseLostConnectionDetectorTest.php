<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\LostConnectionDetector;
use PDOException;
use PHPUnit\Framework\TestCase;

class DatabaseLostConnectionDetectorTest extends TestCase
{
    public function testItDetectsLostConnectionFromMysql2002ErrorInfo(): void
    {
        $exception = new PDOException("SQLSTATE[HY000] [2002] Connexion terminée par expiration du délai d'attente");
        $exception->errorInfo = ['HY000', 2002, null];

        $this->assertTrue((new LostConnectionDetector)->causedByLostConnection($exception));
    }

    public function testItDoesNotDetectNonLostMysqlErrorInfoAsLostConnection(): void
    {
        $exception = new PDOException("SQLSTATE[HY000] [1045] Accès refusé pour l'utilisateur");
        $exception->errorInfo = ['HY000', 1045, null];

        $this->assertFalse((new LostConnectionDetector)->causedByLostConnection($exception));
    }
}
