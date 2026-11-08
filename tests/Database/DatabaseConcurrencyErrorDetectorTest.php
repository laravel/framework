<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\ConcurrencyErrorDetector;
use PDOException;
use PHPUnit\Framework\TestCase;
use Throwable;

class DatabaseConcurrencyErrorDetectorTest extends TestCase
{
    protected function tearDown(): void
    {
        ConcurrencyErrorDetector::detectUsing(null);

        parent::tearDown();
    }

    public function test_concurrency_error_detector_can_detect_using_callback()
    {
        $detector = new ConcurrencyErrorDetector;

        $this->assertFalse($detector->causedByConcurrencyError(new PDOException('Write conflict')));

        ConcurrencyErrorDetector::detectUsing(fn (Throwable $e) => str_contains($e->getMessage(), 'Write conflict'));

        $this->assertTrue($detector->causedByConcurrencyError(new PDOException('SQLSTATE[HY000]: General error: 9007 Write conflict, txnStartTS=1')));
        $this->assertTrue($detector->causedByConcurrencyError(new PDOException('SQLSTATE[40001]: Deadlock found when trying to get lock')));
        $this->assertFalse($detector->causedByConcurrencyError(new PDOException('Syntax error')));

        ConcurrencyErrorDetector::detectUsing(null);

        $this->assertFalse($detector->causedByConcurrencyError(new PDOException('Write conflict')));
    }
}
