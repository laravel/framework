<?php

namespace Illuminate\Tests\Foundation\Testing\Constraints;

use PHPUnit\Framework\TestCase;
use Illuminate\Tests\Foundation\FoundationTestResponseTest;

class SeeInOrderTest extends TestCase
{
    public function testAllSeeInOrderAssertionsDetected()
    {
        $test = new FoundationTestResponseTest('testAssertSeeInOrder');
        $test->run();
        // If we get four assertions, that means all of the assertions
        // were detected by PHPUnit, which is what we want to know.
        // Doing it this way allows the test to work even though
        // the global settings don't check for risky tests.
        $this->assertEquals($test->getNumAssertions(), 4);
    }

    public function testAllSeeTextInOrderAssertionsDetected()
    {
        $test = new FoundationTestResponseTest('testAssertSeeTextInOrder');
        $test->run();
        // If we get four assertions, that means all of the assertions
        // were detected by PHPUnit, which is what we want to know.
        // Doing it this way allows the test to work even though
        // the global settings don't check for risky tests.
        $this->assertEquals($test->getNumAssertions(), 4);
    }
}
