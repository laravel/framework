<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\Date;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationDateRuleTest extends TestCase
{
    public function testValidationPassesWhenPassingCorrectDate()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'birth_date' => '25-07-2000',
            ],
            [
                'birth_date' => Date::default(),
            ]
        );

        $this->assertFalse($v->fails());
    }

    public function testValidationPassesWhenPassingInstanceOfDate()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'birth_date' => now(),
            ],
            [
                'birth_date' => Date::default(),
            ]
        );

        $this->assertFalse($v->fails());
    }

    public function testValidationFailsWhenProvidingDifferentType()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'birth_date' => '10',
            ],
            [
                'birth_date' => Date::default(),
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertSame(['birth_date' => ['validation.date']], $v->messages()->toArray());
    }

    public function testValidationFailsWhenProvidingNull()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'birth_date' => null,
            ],
            [
                'birth_date' => Date::default(),
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertSame(['birth_date' => ['validation.date']], $v->messages()->toArray());
    }

    public function testValidationFailsWhenProvidingAfter()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'birth_date' => now(),
            ],
            [
                'birth_date' => Date::default()->after('tomorrow'),
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertSame(['birth_date' => ['validation.after']], $v->messages()->toArray());
    }

    public function testValidationFailsWhenProvidingBefore()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'birth_date' => now(),
            ],
            [
                'birth_date' => Date::default()->before('tomorrow'),
            ]
        );

        $this->assertFalse($v->fails());
        $this->assertEmpty($v->messages()->toArray());
    }

    public function testValidationFailsWhenProvidingAfterOrEqual()
    {
        $todayDate = date('m/d/Y');

        $v = new Validator(
            resolve('translator'),
            [
                'birth_date' => now()->addWeek(),
            ],
            [
                'birth_date' => Date::default()->afterOrEqual($todayDate),
            ]
        );

        $this->assertFalse($v->fails());
        $this->assertEmpty($v->messages()->toArray());
    }

    public function testValidationFailsWhenProvidingBeforeAntherFailed()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'end_date' => now()->addWeek(),
                'start_date' => now()
            ],
            [
                'end_date' => Date::default()->before('start_date'),
                'start_date' => Date::default()->after('tomorrow'),
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertSame(
            [
                'end_date' => ['validation.before'],
                'start_date' => ['validation.after'],
            ],
            $v->messages()->toArray()
        );
    }

    public function testValidationFailsWhenProvidingBeforeOrEqual()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'end_date' => now()->addWeek(),
                'start_date' => now()
            ],
            [
                'end_date' => Date::default()->beforeOrEqual('start_date'),
                'start_date' => Date::default()->after('tomorrow'),
            ]
        );
        
        $this->assertTrue($v->fails());
        $this->assertSame(
            [
                'end_date' => ['validation.before_or_equal'],
                'start_date' => ['validation.after'],
            ],
            $v->messages()->toArray()
        );
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader,
                'en'
            );
        });

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);

        Date::$defaultCallback = null;
    }
}
