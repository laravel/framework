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

    protected function passes($rule, $values)
    {
        $this->assertValidationRules($rule, $values, true, []);
    }

    public function testStrict()
    {
        $this->fails(
            (new Email())->rfcCompliant(true),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(true),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->fails(
            (new Email())->rfcCompliant(true),
            'username@sub..example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(true),
            'username@sub..example.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->rfcCompliant(true),
            'plainaddress@example.com',
        );

        $this->passes(
            Rule::email()->rfcCompliant(true),
            'plainaddress@example.com',
        );
    }

    public function testDns()
    {
        $this->fails(
            (new Email())->verifyMxRecord(),
            'plainaddress@example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->verifyMxRecord(),
            'plainaddress@example.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->verifyMxRecord(),
            'taylor@laravel.com',
        );

        $this->passes(
            Rule::email()->verifyMxRecord(),
            'taylor@laravel.com',
        );
    }

    public function testSpoof()
    {
        $this->fails(
            (new Email())->preventEmailSpoofing(),
            'admin@examрle.com',// Contains a Cyrillic 'р' (U+0440), not a Latin 'p'
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->preventEmailSpoofing(),
            'admin@examрle.com',// Contains a Cyrillic 'р' (U+0440), not a Latin 'p'
            ['validation.email'],
        );

        $spoofingEmail = 'admin@exam'."\u{0440}".'le.com';
        $this->fails(
            (new Email())->preventEmailSpoofing(),
            $spoofingEmail,
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->preventEmailSpoofing(),
            $spoofingEmail,
            ['validation.email'],
        );

        $this->passes(
            (new Email())->preventEmailSpoofing(),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->preventEmailSpoofing(),
            'admin@example.com',
        );

        $this->passes(
            (new Email())->preventEmailSpoofing(),
            'test👨‍💻@domain.com',
        );

        $this->passes(
            Rule::email()->preventEmailSpoofing(),
            'test👨‍💻@domain.com',
        );
    }

    public function testFilter()
    {
        $this->fails(
            (new Email())->nativeFilter(),
            'tést@domain.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->nativeFilter(),
            'tést@domain.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->nativeFilter(),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->nativeFilter(),
            'admin@example.com',
        );
    }

    public function testFilterUnicode()
    {
        $this->fails(
            (new Email())->nativeFilter(true),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->nativeFilter(true),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->nativeFilter(true),
            'tést@domain.com',
        );

        $this->passes(
            Rule::email()->nativeFilter(true),
            'tést@domain.com',
        );

        $this->passes(
            (new Email())->nativeFilter(true),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->nativeFilter(true),
            'admin@example.com',
        );
    }

    public function testRfc()
    {
        $this->fails(
            (new Email())->rfcCompliant(),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(),
            'invalid.@example.com',
            ['validation.email'],
        );

        $this->fails(
            (new Email())->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->rfcCompliant(),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->rfcCompliant(),
            'admin@example.com',
        );

        $this->passes(
            (new Email())->rfcCompliant(),
            'tést@domain.com',
        );

        $this->passes(
            Rule::email()->rfcCompliant(),
            'tést@domain.com',
        );
    }

    public function testCombiningRules()
    {
        $this->passes(
            (new Email())->rfcCompliant(true)->preventEmailSpoofing(),
            'test@example.com',
        );

        $this->passes(
            Rule::email()->rfcCompliant(true)->preventEmailSpoofing(),
            'test@example.com',
        );

        $this->fails(
            (new Email())->rfcCompliant(true)->preventEmailSpoofing()->verifyMxRecord(),
            'test@example.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(true)->preventEmailSpoofing()->verifyMxRecord(),
            'test@example.com',
            ['validation.email'],
        );

        $this->passes(
            (new Email())->preventEmailSpoofing(),
            'test👨‍💻@domain.com',
        );

        $this->passes(
            Rule::email()->preventEmailSpoofing(),
            'test👨‍💻@domain.com',
        );

        $this->fails(
            (new Email())->preventEmailSpoofing()->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->preventEmailSpoofing()->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['validation.email'],
        );

        $spoofingEmail = 'admin@exam'."\u{0440}".'le.com';

        $this->passes(
            (new Email())->rfcCompliant(),
            $spoofingEmail,
        );

        $this->passes(
            Rule::email()->rfcCompliant(),
            $spoofingEmail,
        );

        $this->fails(
            (new Email())->rfcCompliant()->preventEmailSpoofing(),
            $spoofingEmail,
            ['validation.email'],
        );

        $this->fails(
            Rule::email()->rfcCompliant()->preventEmailSpoofing(),
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

        $spoofingEmail = 'admin@exam'."\u{0440}".'le.com';

        $this->passes(
            Email::default(),
            $spoofingEmail,
        );

        Email::defaults(function () {
            return (new Email())->preventEmailSpoofing();
        });

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ['validation.email'],
        );

        Email::defaults(function () {
            return Rule::email()->rfcCompliant();
        });

        $this->passes(
            Email::default(),
            $spoofingEmail,
        );

        Email::defaults(function () {
            return Rule::email()->preventEmailSpoofing();
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
