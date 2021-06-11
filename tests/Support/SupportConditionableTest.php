<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Traits\Conditionable;
use PHPUnit\Framework\TestCase;

class SupportConditionableTest extends TestCase
{
    public function testWhenConditionCallback()
    {
        $logger = (new ConditionableLogger())
            ->when(2, function ($logger, $condition) {
                $logger->log('when', $condition);
            }, function () {
                $logger->log('default', $condition);
            });

        $this->assertSame(['when', 2], $logger->values);
    }

    public function testWhenDefaultCallback()
    {
        $logger = (new ConditionableLogger())
            ->when(null, function () {
                $logger->log('when', $condition);
            }, function ($logger, $condition) {
                $logger->log('default', $condition);
            });

        $this->assertSame(['default', null], $logger->values);
    }

    public function testUnlessConditionCallback()
    {
        $logger = (new ConditionableLogger())
            ->unless(null, function ($logger, $condition) {
                $logger->log('unless', $condition);
            }, function () {
                $logger->log('default', $condition);
            });

        $this->assertSame(['unless', null], $logger->values);
    }

    public function testUnlessDefaultCallback()
    {
        $logger = (new ConditionableLogger())
            ->unless(2, function () {
                $logger->log('unless', $condition);
            }, function ($logger, $condition) {
                $logger->log('default', $condition);
            });

        $this->assertSame(['default', 2], $logger->values);
    }

    public function testWhenProxy()
    {
        $logger = (new ConditionableLogger())
            ->when(true)->log('one')
            ->when(false)->log('two');

        $this->assertSame(['one'], $logger->values);
    }

    public function testUnlessProxy()
    {
        $logger = (new ConditionableLogger())
            ->unless(true)->log('one')
            ->unless(false)->log('two');

        $this->assertSame(['two'], $logger->values);
    }
}

class ConditionableLogger
{
    use Conditionable;

    public $values = [];

    public function log(...$values)
    {
        array_push($this->values, ...$values);

        return $this;
    }
}
