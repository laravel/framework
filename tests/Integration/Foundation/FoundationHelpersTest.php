<?php

namespace Illuminate\Tests\Integration\Foundation;

use Exception;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class HelpersTest extends TestCase
{
    public function test_rescue()
    {
        $this->assertEquals(rescue(function () {
            throw new Exception;
        }, 'rescued!'), 'rescued!');

        $this->assertEquals(rescue(function () {
            throw new Exception;
        }, function () {
            return 'rescued!';
        }), 'rescued!');

        $this->assertEquals(rescue(function () {
            return 'no need to rescue';
        }, 'rescued!'), 'no need to rescue');

        $testClass = new class {
            public function test(int $a)
            {
                return $a;
            }
        };

        $this->assertEquals(rescue(function () use ($testClass) {
            $testClass->test([]);
        }, 'rescued!'), 'rescued!');
    }
}
