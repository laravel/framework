<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\Phone;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationPhoneRuleTest extends TestCase
{
    public function testString()
    {
        $this->fails('US', Phone::default(), ['01200954866'], [
            'The phone field must be a valid phone number.',
        ]);

        $this->fails('US', Phone::default(), ['+2012009'], [
            'The phone field must be a valid phone number.',
        ]);

        $this->passes('EG', Phone::default(), ['+201200954866']);

        $this->fails('US', Phone::default(), ['+201200954866'], [
            'The phone field must be a valid phone number.',
        ]);
    }

    protected function passes($phoneCountry, $rule, $values)
    {
        $this->assertValidationRules($phoneCountry, $rule, $values, true, []);
    }

    protected function fails($phoneCountry, $rule, $values, $messages)
    {
        $this->assertValidationRules($phoneCountry, $rule, $values, false, $messages);
    }

    protected function assertValidationRules($phoneCountry, $rule, $values, $result, $messages)
    {
        foreach ($values as $value) {
            $v = new Validator(
                resolve('translator'),
                ['phone' => $value, 'phone_country' => $phoneCountry],
                ['phone' => is_object($rule) ? clone $rule : $rule]
            );

            $this->assertSame($result, $v->passes());

            $this->assertSame(
                $result ? [] : ['phone' => $messages],
                $v->messages()->toArray()
            );
        }
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

        Phone::$defaultCallback = null;
    }
}
