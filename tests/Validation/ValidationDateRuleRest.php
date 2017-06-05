<?php

namespace Illuminate\Tests\Validation;

use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Rules\Date;

class ValidationDateRuleRest extends TestCase
{
    public function testDate()
    {
        $rule = new Date();
        $this->assertEquals('date', (string) $rule);

        $rule = new Date('date');
        $this->assertEquals('date', (string) $rule);
    }

    public function testAfter()
    {
        $rule = (new Date('after'))->date('today');
        $this->assertEquals('date|after:today', (string) $rule);

        $rule = (new Date())->after()->date('tomorrow');
        $this->assertEquals('date|after:tomorrow', (string) $rule);

        $rule = new Date('after', 'today');
        $this->assertEquals('date|after:today', (string) $rule);
    }

    public function testAfterOrEqual()
    {
        $rule = (new Date('after_or_equal'))->date('today');
        $this->assertEquals('date|after_or_equal:today', (string) $rule);

        $rule = (new Date())->afterOrEqual()->date('tomorrow');
        $this->assertEquals('date|after_or_equal:tomorrow', (string) $rule);

        $rule = new Date('after_or_equal', 'today');
        $this->assertEquals('date|after_or_equal:today', (string) $rule);
    }

    public function testBefore()
    {
        $rule = (new Date('before'))->date('today');
        $this->assertEquals('date|before:today', (string) $rule);

        $rule = (new Date())->before()->date('tomorrow');
        $this->assertEquals('date|before:tomorrow', (string) $rule);

        $rule = new Date('before', 'today');
        $this->assertEquals('date|before:today', (string) $rule);
    }

    public function testBeforeOrEqual()
    {
        $rule = (new Date('before_or_equal'))->date('today');
        $this->assertEquals('date|before_or_equal:today', (string) $rule);

        $rule = (new Date())->beforeOrEqual()->date('tomorrow');
        $this->assertEquals('date|before_or_equal:tomorrow', (string) $rule);

        $rule = new Date('before_or_equal', 'today');
        $this->assertEquals('date|before_or_equal:today', (string) $rule);
    }

    public function testRejectionOfInvalidTypes()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Date('nonexistant');
    }
}
