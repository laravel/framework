<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Tests\Validation\fixtures\ValidatesAttributes;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationServiceProvider;
use PHPUnit\Framework\TestCase;

class ValidationValidateAttributeTest extends TestCase
{
    public function test_validate_method_with_validate_prefix_is_called()
    {
        $v = new ValidatesAttributes(
            resolve('translator'),
            ['foo' => 'bar'],
            ['foo' => 'rule']
        );

        $this->assertTrue($v->passes());
    }

    public function test_validate_method_with_validate_attribute_is_called()
    {
        $v = new ValidatesAttributes(
            resolve('translator'),
            ['foo' => 'bar'],
            ['foo' => 'ruleWithAttribute']
        );

        $this->assertTrue($v->passes());
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
