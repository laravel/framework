<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\OrRule;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationOrRuleTest extends TestCase
{
    public function testValidationPassesWithCorrectOrRuleValue()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'name' => 'Taylor Otwell',
            ],
            [
                'name' => new OrRule('starts_with:Otwell', 'ends_with:Otwell'),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function testValidationFailsWithWrongOrRuleValue()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'name' => 'Thomas Omweri',
            ],
            [
                'name' => new OrRule('starts_with:Otwell', 'ends_with:Otwell'),
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
