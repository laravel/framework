<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Traits\Conditionable;
use PHPUnit\Framework\TestCase;

class SupportConditionableTest extends TestCase
{
    public function testWhenConditionCallback()
    {
        // With static condition
        $logger = (new ConditionableLogger())
            ->when(2, function ($logger, $condition) {
                $logger->log('when', $condition);
            }, function ($logger, $condition) {
                $logger->log('default', $condition);
            });

        $this->assertSame(['when', 2], $logger->values);

        // With callback condition
        $logger = (new ConditionableLogger())->log('init')
            ->when(function ($logger) {
                return $logger->has('init');
            }, function ($logger, $condition) {
                $logger->log('when', $condition);
            }, function ($logger, $condition) {
                $logger->log('default', $condition);
            });

        $this->assertSame(['init', 'when', true], $logger->values);
    }

    public function testWhenDefaultCallback()
    {
        // With static condition
        $logger = (new ConditionableLogger())
            ->when(null, function ($logger, $condition) {
                $logger->log('when', $condition);
            }, function ($logger, $condition) {
                $logger->log('default', $condition);
            });

        $this->assertSame(['default', null], $logger->values);

        // With callback condition
        $logger = (new ConditionableLogger())
            ->when(function ($logger) {
                return $logger->has('missing');
            }, function ($logger, $condition) {
                $logger->log('when', $condition);
            }, function ($logger, $condition) {
                $logger->log('default', $condition);
            });

        $this->assertSame(['default', false], $logger->values);
    }

    public function testWhenNotNullConditionCallback()
    {
        // With falsy but non-null values
        foreach ([0, '0', false, ''] as $value) {
            $logger = (new ConditionableLogger())
                ->whenNotNull($value, function ($logger, $value) {
                    $logger->log('when', $value);
                }, function ($logger, $value) {
                    $logger->log('default', $value);
                });

            $this->assertSame(['when', $value], $logger->values);
        }

        // With callback condition
        $logger = (new ConditionableLogger())
            ->whenNotNull(function ($logger) {
                return 0;
            }, function ($logger, $value) {
                $logger->log('when', $value);
            }, function ($logger, $value) {
                $logger->log('default', $value);
            });

        $this->assertSame(['when', 0], $logger->values);
    }

    public function testWhenNotNullDefaultCallback()
    {
        // With static condition
        $logger = (new ConditionableLogger())
            ->whenNotNull(null, function ($logger, $value) {
                $logger->log('when', $value);
            }, function ($logger, $value) {
                $logger->log('default', $value);
            });

        $this->assertSame(['default', null], $logger->values);

        // With callback condition
        $logger = (new ConditionableLogger())
            ->whenNotNull(function ($logger) {
                return null;
            }, function ($logger, $value) {
                $logger->log('when', $value);
            }, function ($logger, $value) {
                $logger->log('default', $value);
            });

        $this->assertSame(['default', null], $logger->values);

        // Without default callback
        $logger = (new ConditionableLogger())
            ->whenNotNull(null, function ($logger, $value) {
                $logger->log('when', $value);
            });

        $this->assertSame([], $logger->values);
    }

    public function testWhenNotNullProxy()
    {
        // With static condition
        $logger = (new ConditionableLogger())
            ->whenNotNull(0)->log('one')
            ->whenNotNull(null)->log('two')
            ->whenNotNull()->log('three');

        $this->assertSame(['one'], $logger->values);

        // With callback condition
        $logger = (new ConditionableLogger())
            ->whenNotNull(function ($logger) {
                return false;
            })
            ->log('one')
            ->whenNotNull(function ($logger) {
                return null;
            })
            ->log('two');

        $this->assertSame(['one'], $logger->values);
    }

    public function testUnlessConditionCallback()
    {
        // With static condition
        $logger = (new ConditionableLogger())
            ->unless(null, function ($logger, $condition) {
                $logger->log('unless', $condition);
            }, function ($logger, $condition) {
                $logger->log('default', $condition);
            });

        $this->assertSame(['unless', null], $logger->values);

        // With callback condition
        $logger = (new ConditionableLogger())
            ->unless(function ($logger) {
                return $logger->has('missing');
            }, function ($logger, $condition) {
                $logger->log('unless', $condition);
            }, function ($logger, $condition) {
                $logger->log('default', $condition);
            });

        $this->assertSame(['unless', false], $logger->values);
    }

    public function testUnlessDefaultCallback()
    {
        // With static condition
        $logger = (new ConditionableLogger())
            ->unless(2, function ($logger, $condition) {
                $logger->log('unless', $condition);
            }, function ($logger, $condition) {
                $logger->log('default', $condition);
            });

        $this->assertSame(['default', 2], $logger->values);

        // With callback condition
        $logger = (new ConditionableLogger())->log('init')
            ->unless(function ($logger) {
                return $logger->has('init');
            }, function ($logger, $condition) {
                $logger->log('unless', $condition);
            }, function ($logger, $condition) {
                $logger->log('default', $condition);
            });

        $this->assertSame(['init', 'default', true], $logger->values);
    }

    public function testWhenProxy()
    {
        // With static condition
        $logger = (new ConditionableLogger())
            ->when(true)->log('one')
            ->when(false)->log('two');

        $this->assertSame(['one'], $logger->values);

        // With callback condition
        $logger = (new ConditionableLogger())->log('init')
            ->when(function ($logger) {
                return $logger->has('init');
            })
            ->log('one')
            ->when(function ($logger) {
                return $logger->has('missing');
            })
            ->log('two')
            ->when()->has('init')->log('three')
            ->when()->has('missing')->log('four')
            ->when()->toggle->log('five')
            ->toggle()
            ->when()->toggle->log('six');

        $this->assertSame(['init', 'one', 'three', 'six'], $logger->values);
    }

    public function testUnlessProxy()
    {
        // With static condition
        $logger = (new ConditionableLogger())
            ->unless(true)->log('one')
            ->unless(false)->log('two');

        $this->assertSame(['two'], $logger->values);

        // With callback condition
        $logger = (new ConditionableLogger())->log('init')
            ->unless(function ($logger) {
                return $logger->has('init');
            })
            ->log('one')
            ->unless(function ($logger) {
                return $logger->has('missing');
            })
            ->log('two')
            ->unless()->has('init')->log('three')
            ->unless()->has('missing')->log('four')
            ->unless()->toggle->log('five')
            ->toggle()
            ->unless()->toggle->log('six');

        $this->assertSame(['init', 'two', 'four', 'five'], $logger->values);
    }
}

class ConditionableLogger
{
    use Conditionable;

    public $values = [];

    public $toggle = false;

    public function log(...$values)
    {
        array_push($this->values, ...$values);

        return $this;
    }

    public function has($value)
    {
        return in_array($value, $this->values);
    }

    public function toggle()
    {
        $this->toggle = ! $this->toggle;

        return $this;
    }
}
