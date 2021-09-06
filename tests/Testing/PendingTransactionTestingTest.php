<?php

namespace Illuminate\Tests\Testing;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;

class PendingTransactionTestingTest extends TestCase
{
    public function testItThrowsAnExceptionIfPendingTransaction()
    {
        $test = new TestSuite();
        $test->addTestSuite(PendingTransactionTesting::class);
        $result = $test->run();

        $this->assertCount(1, $result->errors());
        $this->assertSame('DomainException: Invalid transaction level: 1', trim($result->errors()[0]->getExceptionAsString()));
    }
}
