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
    private const ATTRIBUTE = 'my_email';
    private const ATTRIBUTE_REPLACED = 'my email';

    public function testBasic()
    {
        $this->fails(
            Email::default(),
            'foo',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );
        $this->fails(
            Rule::email(),
            'foo',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
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

    /**
     * @param  mixed  $rule
     * @param  string|array $values
     * @param  array  $expectedMessages
     * @param  string|null  $customValidationMessage
     * @return void
     */
    protected function fails($rule, $values, $expectedMessages, $customValidationMessage = null)
    {
        $this->assertValidationRules($rule, $values, false, $expectedMessages, $customValidationMessage);
    }

    /**
     * @param  mixed  $rule
     * @param  string|array  $values
     * @param  bool  $expectToPass
     * @param  array  $expectedMessages
     * @param  string|null  $customValidationMessage
     * @return void
     */
    protected function assertValidationRules($rule, $values, $expectToPass, $expectedMessages = [], $customValidationMessage = null)
    {
        $values = Arr::wrap($values);

        $translator = resolve('translator');

        foreach ($values as $value) {
            $v = new Validator(
                $translator,
                [self::ATTRIBUTE => $value],
                [self::ATTRIBUTE => is_object($rule) ? clone $rule : $rule],
                $customValidationMessage ? [self::ATTRIBUTE.'.email' => $customValidationMessage] : []
            );

            $this->assertSame($expectToPass, $v->passes());

            $this->assertSame(
                $expectToPass ? [] : [self::ATTRIBUTE => $expectedMessages],
                $v->messages()->toArray()
            );
        }
    }

    /**
     * @param mixed $rule
     * @param string|array $values
     * @return void
     */
    protected function passes($rule, $values)
    {
        $this->assertValidationRules($rule, $values, true);
    }

    public function testStrict()
    {
        $this->fails(
            (new Email())->rfcCompliant(true),
            'invalid.@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(true),
            'invalid.@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            (new Email())->rfcCompliant(true),
            'username@sub..example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(true),
            'username@sub..example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
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
            (new Email())->validateMxRecord(),
            'plainaddress@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->validateMxRecord(),
            'plainaddress@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->passes(
            (new Email())->validateMxRecord(),
            'taylor@laravel.com',
        );

        $this->passes(
            Rule::email()->validateMxRecord(),
            'taylor@laravel.com',
        );
    }

    public function testSpoof()
    {
        $this->fails(
            (new Email())->preventSpoofing(),
            'admin@examрle.com',// Contains a Cyrillic 'р' (U+0440), not a Latin 'p'
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->preventSpoofing(),
            'admin@examрle.com',// Contains a Cyrillic 'р' (U+0440), not a Latin 'p'
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $spoofingEmail = 'admin@exam'."\u{0440}".'le.com';
        $this->fails(
            (new Email())->preventSpoofing(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->preventSpoofing(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->passes(
            (new Email())->preventSpoofing(),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->preventSpoofing(),
            'admin@example.com',
        );

        $this->passes(
            (new Email())->preventSpoofing(),
            'test👨‍💻@domain.com',
        );

        $this->passes(
            Rule::email()->preventSpoofing(),
            'test👨‍💻@domain.com',
        );
    }

    public function testFilter()
    {
        $this->fails(
            (new Email())->withNativeValidation(),
            'tést@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->withNativeValidation(),
            'tést@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->passes(
            (new Email())->withNativeValidation(),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->withNativeValidation(),
            'admin@example.com',
        );
    }

    public function testFilterUnicode()
    {
        $this->fails(
            (new Email())->withNativeValidation(true),
            'invalid.@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->withNativeValidation(true),
            'invalid.@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->passes(
            (new Email())->withNativeValidation(true),
            'tést@domain.com',
        );

        $this->passes(
            Rule::email()->withNativeValidation(true),
            'tést@domain.com',
        );

        $this->passes(
            (new Email())->withNativeValidation(true),
            'admin@example.com',
        );

        $this->passes(
            Rule::email()->withNativeValidation(true),
            'admin@example.com',
        );
    }

    public function testRfc()
    {
        $this->fails(
            (new Email())->rfcCompliant(),
            'invalid.@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(),
            'invalid.@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            (new Email())->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
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
            (new Email())->rfcCompliant(true)->preventSpoofing(),
            'test@example.com',
        );

        $this->passes(
            Rule::email()->rfcCompliant(true)->preventSpoofing(),
            'test@example.com',
        );

        $this->fails(
            (new Email())->rfcCompliant(true)->preventSpoofing()->validateMxRecord(),
            'test@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(true)->preventSpoofing()->validateMxRecord(),
            'test@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->passes(
            (new Email())->preventSpoofing(),
            'test👨‍💻@domain.com',
        );

        $this->passes(
            Rule::email()->preventSpoofing(),
            'test👨‍💻@domain.com',
        );

        $this->fails(
            (new Email())->preventSpoofing()->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->preventSpoofing()->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
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
            (new Email())->rfcCompliant()->preventSpoofing(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->rfcCompliant()->preventSpoofing(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
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
            return (new Email())->preventSpoofing();
        });

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        Email::defaults(function () {
            return Rule::email()->rfcCompliant();
        });

        $this->passes(
            Email::default(),
            $spoofingEmail,
        );

        Email::defaults(function () {
            return Rule::email()->preventSpoofing();
        });

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );
    }

    public function testValidationMessages()
    {
        Email::defaults(function () {
            return Rule::email()->preventSpoofing();
        });

        $spoofingEmail = 'admin@exam'."\u{0440}".'le.com';

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
            'The :attribute must be a valid email address.',
        );

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ["Please check the entered ".self::ATTRIBUTE_REPLACED.", it must be a valid email address, {$spoofingEmail} given."],
            'Please check the entered :attribute, it must be a valid email address, :input given.'
        );

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ['Plain text value'],
            'Plain text value'
        );
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            $translator = new Translator(
                new ArrayLoader, 'en'
            );

            $translator->addLines([
                'validation.email' => 'The :attribute must be a valid email address.',
            ], 'en');

            return $translator;
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
