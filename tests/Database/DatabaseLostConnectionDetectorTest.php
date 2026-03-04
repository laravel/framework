<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\LostConnectionDetector;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseLostConnectionDetectorTest extends TestCase
{
    public function testReturnsTrueForMysql2002WithLocalizedMessage()
    {
        $detector = new LostConnectionDetector;

        $exception = $this->createPdoExceptionWithErrorInfo(
            'Connexion terminee par expiration du delai d\'attente',
            ['HY000', 2002, 'Connexion terminee par expiration du delai d\'attente'],
        );

        $this->assertTrue($detector->causedByLostConnection($exception));
    }

    public function testReturnsTrueForQueryExceptionWrapping2002()
    {
        $detector = new LostConnectionDetector;

        $previous = $this->createPdoExceptionWithErrorInfo(
            'Localized mysql socket failure',
            ['HY000', 2002, 'some text'],
        );

        $exception = new QueryException('mysql', 'SELECT * FROM users WHERE id = ?', [1], $previous);

        $this->assertTrue($detector->causedByLostConnection($exception));
    }

    public function testReturnsTrueForExistingMessageFallback()
    {
        $detector = new LostConnectionDetector;

        $this->assertTrue($detector->causedByLostConnection(
            new RuntimeException('server has gone away')
        ));
    }

    public function testReturnsFalseForUnrelatedPdoErrorCode()
    {
        $detector = new LostConnectionDetector;

        $exception = $this->createPdoExceptionWithErrorInfo(
            'Authentication failed in locale-specific text',
            ['HY000', 1045, 'some unrelated error'],
        );

        $this->assertFalse($detector->causedByLostConnection($exception));
    }

    public function testReturnsFalseWhenSqlstateDiffers()
    {
        $detector = new LostConnectionDetector;

        $exception = $this->createPdoExceptionWithErrorInfo(
            'Localized transport failure with unmatched SQLSTATE',
            ['08006', 2002, 'some text'],
        );

        $this->assertFalse($detector->causedByLostConnection($exception));
    }

    public function testReturnsTrueForMysql2002WhenDriverCodeIsString()
    {
        $detector = new LostConnectionDetector;

        $exception = $this->createPdoExceptionWithErrorInfo(
            'Unknown locale-specific message',
            ['HY000', '2002', 'localized text'],
        );

        $this->assertTrue($detector->causedByLostConnection($exception));
    }

    private function createPdoExceptionWithErrorInfo(string $message, array $errorInfo): PDOException
    {
        $exception = new PDOException($message);
        $exception->errorInfo = $errorInfo;

        return $exception;
    }
}
