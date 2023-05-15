<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\AnyOf;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationAnyOfRuleTest extends TestCase
{
    public function testValidationPassesWithCorrectAnyOfRule()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'value' => 'Laravel',
            ],
            [
                'value' => new AnyOf(['string', 'decimal:2', 'integer']),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function testValidationFailsWithWrongAnyOfRule()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'value' => ['Laravel', 'Other'],
            ],
            [
                'value' => new AnyOf(['string', 'decimal:2', 'integer']),
            ]
        );

        $this->assertFalse($validator->passes());
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
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
    }
}
