<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationEmailRuleTest extends TestCase
{
    public function testBasic()
    {
        $this->fails(
            Email::default(),
            'foo',
            ['validation.email'],
        );
        $this->fails(
            Rule::email(),
            'foo',
            ['validation.email'],
        );

        $this->passes(
            Email::default(),
            'taylor@laravel.com',
        );

        $this->passes(
            Rule::email(),
            'taylor@laravel.com',
        );

        $this->passes(Email::default(), null);

        $this->passes(Rule::email(), null);
    }

    protected function fails($rule, $values, $messages)
    {
        $this->assertValidationRules($rule, $values, false, $messages);
    }

    protected function assertValidationRules($rule, $values, $result, $messages)
    {
        $values = Arr::wrap($values);

        foreach ($values as $value) {
            $v = new Validator(
                resolve('translator'),
                ['my_file' => $value],
                ['my_file' => is_object($rule) ? clone $rule : $rule]
            );

            $this->assertSame($result, $v->passes());

            $this->assertSame(
                $result ? [] : ['my_file' => $messages],
                $v->messages()->toArray()
            );
        }
    }

    protected function passes($rule, $values)
    {
        $this->assertValidationRules($rule, $values, true, []);
    }

    public function testStrict()
    {
        $this->fails(
            (new Email())->strict(),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->strict(),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->fails(
            (new Email())->strict(),
            'username@sub..example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->strict(),
            'username@sub..example.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->strict(),
            'plainaddress@example.com',
        );

        $this->passes(
            Rule::email()->strict(),
            'plainaddress@example.com',
        );
    }

    public function testDns()
    {
        $this->fails(
            (new Email())->dns(),
            'plainaddress@example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->dns(),
            'plainaddress@example.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->dns(),
            'taylor@laravel.com',
        );

        $this->passes(
            Rule::email()->dns(),
            'taylor@laravel.com',
        );
    }

    public function testSpoof()
    {
        $this->fails(
            (new Email())->spoof(),
            'admin@examрle.com',// Contains a Cyrillic 'р' (U+0440), not a Latin 'p'
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->spoof(),
            'admin@examрle.com',// Contains a Cyrillic 'р' (U+0440), not a Latin 'p'
            ['validation.email'],
        );

        $email = 'admin@exam' . "\u{0440}" . 'le.com';
        $this->fails(
            (new Email())->spoof(),
            $email,
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->spoof(),
            $email,
            ['validation.email'],
        );

        $this->passes(
            (new Email())->spoof(),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->spoof(),
            'admin@example.com',
        );

        $this->passes(
            (new Email())->spoof(),
            'test👨‍💻@domain.com',
        );

        $this->passes(
            Rule::email()->spoof(),
            'test👨‍💻@domain.com',
        );
    }

    public function testFilter()
    {
        $this->fails(
            (new Email())->filter(),
            'tést@domain.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->filter(),
            'tést@domain.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->filter(),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->filter(),
            'admin@example.com',
        );
    }

    public function testFilterUnicode()
    {
        $this->fails(
            (new Email())->filterUnicode(),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->filterUnicode(),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->filterUnicode(),
            'tést@domain.com',
        );

        $this->passes(
            Rule::email()->filterUnicode(),
            'tést@domain.com',
        );

        $this->passes(
            (new Email())->filterUnicode(),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->filterUnicode(),
            'admin@example.com',
        );
    }

    public function testRfc()
    {
        $this->fails(
            (new Email())->rfc(),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->rfc(),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->fails(
            (new Email())->rfc(),
            'test👨‍💻@domain.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->rfc(),
            'test👨‍💻@domain.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->rfc(),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->rfc(),
            'admin@example.com',
        );

        $this->passes(
            (new Email())->rfc(),
            'tést@domain.com',
        );

        $this->passes(
            Rule::email()->rfc(),
            'tést@domain.com',
        );
    }

    public function testCombiningRules()
    {
        $this->passes(
            (new Email())->rfc()->strict()->spoof(),
            'test@example.com',
        );

        $this->passes(
            Rule::email()->rfc()->strict()->spoof(),
            'test@example.com',
        );

        $this->fails(
            (new Email())->rfc()->strict()->spoof()->dns(),
            'test@example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->rfc()->strict()->spoof()->dns(),
            'test@example.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->spoof(),
            'test👨‍💻@domain.com',
        );

        $this->passes(
            Rule::email()->spoof(),
            'test👨‍💻@domain.com',
        );

        $this->fails(
            (new Email())->spoof()->rfc(),
            'test👨‍💻@domain.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->spoof()->rfc(),
            'test👨‍💻@domain.com',
            ['validation.email'],
        );

        $spoofingEmail = 'admin@exam' . "\u{0440}" . 'le.com';

        $this->passes(
            (new Email())->rfc(),
            $spoofingEmail,
        );

        $this->passes(
            Rule::email()->rfc(),
            $spoofingEmail,
        );

        $this->fails(
            (new Email())->rfc()->spoof(),
            $spoofingEmail,
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->rfc()->spoof(),
            $spoofingEmail,
            ['validation.email'],
        );
    }

    public function testMacro()
    {
        Email::macro('laravelEmployee', function () {
            return static::default()->rules('ends_with:@laravel.com');
        });

        $this->fails(
            Email::laravelEmployee(),
            'taylor@example.com',
            ['validation.ends_with']
        );

        $this->fails(
            Rule::email()->laravelEmployee(),
            'taylor@example.com',
            ['validation.ends_with']
        );

        $this->passes(
            Email::laravelEmployee(),
            'taylor@laravel.com',
        );

        $this->passes(
            Rule::email()->laravelEmployee(),
            'taylor@laravel.com',
        );
    }

    public function testItCanSetDefaultUsing()
    {
        $this->assertInstanceOf(Email::class, Email::default());

        $spoofingEmail = 'admin@exam' . "\u{0440}" . 'le.com';

        $this->passes(
            Email::default(),
            $spoofingEmail,
        );

        Email::defaults(function () {
            return (new Email())->spoof();
        });

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ['validation.email'],
        );

        Email::defaults(function () {
            return Rule::email()->rfc();
        });

        $this->passes(
            Email::default(),
            $spoofingEmail,
        );

        Email::defaults(function () {
            return Rule::email()->spoof();
        });

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ['validation.email'],
        );
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
