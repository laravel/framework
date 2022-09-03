<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationBooleanRuleTest extends TestCase
{
    public function testCorrectValues()
    {
        $this->passes(true);
        $this->passes(false);

        $this->passes('true');
        $this->passes('false');

        $this->passes(1);
        $this->passes(0);

        $this->passes('1');
        $this->passes('0');

        $this->passes('');
    }

    public function testIncorrectValues()
    {
        $this->fails('True');
        $this->fails('False');

        $this->fails('on');
        $this->fails('off');

        $this->fails('On');
        $this->fails('Off');

        $this->fails('yes');
        $this->fails('no');

        $this->fails('Yes');
        $this->fails('No');

        $this->fails(null);

        $this->fails(12);
        $this->fails('12');

        $this->fails('custom');

        $this->fails([]);

        $this->fails(new Foo());
        $this->fails(Foo::class);
    }

    protected function fails($value)
    {
        $this->assertValidationRules($value, false, ['value' => ['validation.boolean']]);
    }

    protected function passes($value)
    {
        $this->assertValidationRules($value, true, []);
    }

    protected function assertValidationRules($value, $result, $message)
    {
        $v = new Validator(
            resolve('translator'),
            ['value' => $value],
            ['value' => Rule::boolean()]
        );

        $this->assertSame($result, $v->passes());
        $this->assertSame($message, $v->messages()->toArray());
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

class Foo
{
}
