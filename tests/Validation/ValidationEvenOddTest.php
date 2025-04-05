<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationEvenOddTest extends TestCase
{
    public function testValidationPassesWithEvenNumber()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'number' => 2,
            ],
            [
                'number' => 'even',
            ]
        );

        $this->assertFalse($v->fails());
    }

    public function testValidationFailsWithEvenNumber()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'number' => 3,
            ],
            [
                'number' => 'even',
            ]
        );

        $this->assertTrue($v->fails());
    }

    public function testValidationPassWithOddNumber()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'number' => 3,
            ],
            [
                'number' => 'odd',
            ]
        );

        $this->assertFalse($v->fails());
    }

    public function testValidationFailsWithOddNumber()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'number' => 4,
            ],
            [
                'number' => 'odd',
            ]
        );

        $this->assertTrue($v->fails());
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
