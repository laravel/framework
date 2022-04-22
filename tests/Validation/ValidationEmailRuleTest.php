<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationEmailRuleTest extends TestCase
{
    public function testString()
    {
        $this->fails(Email::rfc(), [['foo' => 'bar'], ['foo']], [
            'validation.string',
        ]);

        $this->fails(Email::rfc(), [1234567, 545], [
            'validation.string',
        ]);
    }

    public function testConditional()
    {
        $is_privileged_user = true;
        $rule = (new Email)->when($is_privileged_user, function ($rule) {
            $rule->strict();
        });

        $this->fails($rule, ['aaaaaaaa', 'foo()@bar.com'], [
            'The my email must be a valid email address.',
        ]);

        $is_privileged_user = false;
        $rule = (new Email)->when($is_privileged_user, function ($rule) {
            $rule->strict();
        });

        $this->passes($rule, ['foo@example.com', 'bär@example.com']);
    }

    public function testRfc()
    {
        $this->fails(Email::rfc(), ['foo@.com'], [
            'The my email must be a valid email address.'
        ]);

        $this->passes(Email::rfc(), ['foo@gmail.com']);
    }

    public function testStrict()
    {
        $this->fails(Email::rfc()->strict(), ['foo@bar' ], [
            'The my email must be a valid email address.',
        ]);

        $this->fails(Email::rfc()->strict(), ['foo()@bar.com' ], [
            'The my email must be a valid email address.',
        ]);

        $this->passes(Email::rfc()->strict(), ['foo@example.com']);
    }

    public function testDns()
    {
        $this->fails(Email::rfc()->dns(), ['foo@example.com' ], [
            'The my email does not have a valid domain.',
        ]);

        $this->passes(Email::rfc()->dns(), ['foo@gmail.com']);
    }

    public function testSpoof()
    {
        $this->fails(Email::rfc()->spoof(), ['Кириллица@example.com' ], [
            'The my email appears to be spoofed.',
        ]);

        $this->passes(Email::rfc()->spoof(), ['foo@gmail.com']);
    }

    public function testFilter()
    {
        $this->fails(Email::rfc()->filter(), ['foö@example.com' ], [
            'The my email must pass the filter.',
        ]);

        $this->passes(Email::rfc()->filter(), ['foo@gmail.com']);
    }

    public function testMultiple()
    {
        $this->fails(Email::rfc()->strict()->dns()->spoof()->filter(), ['Кириfoö()@example.com' ], [
            'The my email must be a valid email address.',
            'The my email does not have a valid domain.',
            'The my email appears to be spoofed.',
            'The my email must pass the filter.',
        ]);

        $this->passes(Email::rfc()->strict()->dns()->spoof()->filter(), ['foo@gmail.com']);
    }

    protected function passes($rule, $values)
    {
        $this->assertValidationRules($rule, $values, true, []);
    }

    protected function fails($rule, $values, $messages)
    {
        $this->assertValidationRules($rule, $values, false, $messages);
    }

    protected function assertValidationRules($rule, $values, $result, $messages)
    {
        foreach ($values as $value) {
            $v = new Validator(
                resolve('translator'),
                ['my_email' => $value],
                ['my_email' => is_object($rule) ? clone $rule : $rule]
            );

            $this->assertSame($result, $v->passes());

            $this->assertSame(
                $result ? [] : ['my_email' => $messages],
                $v->messages()->toArray()
            );
        }
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

        Email::$defaultCallback = null;
    }
}
