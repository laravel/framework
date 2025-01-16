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
     * @param  string|array  $values
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

            $this->assertSame($expectToPass, $v->passes(), 'Expected email input '.$value.' to '.($expectToPass ? 'pass' : 'fail').'.');

            $this->assertSame(
                $expectToPass ? [] : [self::ATTRIBUTE => $expectedMessages],
                $v->messages()->toArray(),
                'Expected different message for email input '.$value,
            );
        }
    }

    /**
     * @param  mixed  $rule
     * @param  string|array  $values
     * @return void
     */
    protected function passes($rule, $values)
    {
        $this->assertValidationRules($rule, $values, true);
    }

    public function testRfcCompliantStrict()
    {
        $emailThatFailsBothNonStrictButFailsInStrict = 'username@sub..example.com';
        $emailThatPassesNonStrictButFailsInStrict = '"has space"@example.com';
        $emailThatPassesBothNonStrictAndInStrict = 'plainaddress@example.com';

        $this->fails(
            (new Email())->rfcCompliant(strict: true),
            $emailThatPassesNonStrictButFailsInStrict,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(strict: true),
            $emailThatPassesNonStrictButFailsInStrict,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            (new Email())->rfcCompliant(strict: true),
            $emailThatFailsBothNonStrictButFailsInStrict,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(strict: true),
            $emailThatFailsBothNonStrictButFailsInStrict,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->passes(
            (new Email())->rfcCompliant(strict: true),
            $emailThatPassesBothNonStrictAndInStrict,
        );

        $this->passes(
            Rule::email()->rfcCompliant(strict: true),
            $emailThatPassesBothNonStrictAndInStrict,
        );
    }

    public function testValidateMxRecord()
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

    public function testPreventSpoofing()
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

    public function testWithNativeValidation()
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

    public function testWithNativeValidationAllowUnicode()
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

    public function testRfcCompliantNonStrict()
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

    public function testPassesRfcCompliantButNotRfcCompliantStrict()
    {
        $emailsThatPassOnRfcCompliantButFailOnStrict = [
            '"has space"@example.com',              // Quoted local part with space
            'some(comment)@example.com',            // Comment in local part
            'abc."test"@example.com',               // Mixed quoted/unquoted local part
            '"escaped\\\"quote"@example.com',       // Escaped quote inside quoted local part
            'test@example',                         // Domain without TLD
            'test@localhost',                       // Domain without TLD
            'name@[127.0.0.1]',                     // Local-part with domain-literal IPv4 address
            'user@[IPv6:::1]',                      // Domain-literal with unusual IPv6 short form
            'a@[IPv6:2001:db8::1]',                 // Domain-literal with normal IPv6
            'user@[IPv6:::]',                       // invalid shorthand IPv6
        ];

        foreach ($emailsThatPassOnRfcCompliantButFailOnStrict as $email) {
            $this->passes(
                Rule::email()->rfcCompliant(),
                $email,
            );

            $this->fails(
                Rule::email()->rfcCompliant(strict: true),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.'],
            );
        }

        $emailsThatPassOnBothRfcCompliantAndOnStrict = [
            'plainaddress@example.com',
            'joe.smith@example.io',
            'custom-tag+dev@example.org',
            'hyphens--@example.org',
            'underscore_name@example.co.uk',
            'underscores__@example.org',
            'user@subdomain.example.com',
            'numbers123@domain.com',
            'john-doe@some-domain.com',
            'UPPERlower@example.org',
            'dots.ok@sub.domain.io',
            'some_email+tag@domain.dev',
            'a@b.c',
            'user@xn--bcher-kva.example',
            'user@bücher.example',
        ];

        foreach ($emailsThatPassOnBothRfcCompliantAndOnStrict as $email) {
            $this->passes(
                Rule::email()->rfcCompliant(),
                $email,
            );

            $this->passes(
                Rule::email()->rfcCompliant(strict: true),
                $email,
            );
        }

        $emailsThatFailOnBoth = [
            'invalid.@example.com',
            'invalid@.example.com',
            '.invalid@example.com',
            'invalid@example.com.',
            'some..dots@example.com',
            'username@sub..example.com',
            'test@example..com',
            'test@@example.com',
            'test👨‍💻@domain.com',
            'username@domain-with-hyphen-.com',
            '()<>[]:,;@example.com',
            '@example.com',
            '[test]@example.com',
            'user@example.com:3000',
            '"unescaped"quote@example.com',
            'https://example.com',
            'with\\escape@example.com',
        ];

        foreach ($emailsThatFailOnBoth as $email) {
            $this->fails(
                Rule::email()->rfcCompliant(),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.'],
            );

            $this->fails(
                Rule::email()->rfcCompliant(strict: true),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.'],
            );
        }

        $emailsThatPassOnBoth = [
            'plainaddress@example.com',
            'tést@example.com',
            'user@üñîçødé.com',
            'test@xn--bcher-kva.com',
        ];

        foreach ($emailsThatPassOnBoth as $email) {
            $this->passes(
                Rule::email()->rfcCompliant(),
                $email,
            );

            $this->passes(
                Rule::email()->rfcCompliant(strict: true),
                $email,
            );
        }
    }

    public function testWithNativeValidationAsciiVsUnicode()
    {
        /**
         * Emails that fail ASCII-only native validation but pass with Unicode turned on.
         */
        $emailsThatFailOnAsciiButPassOnUnicode = [
            'déjà@example.com',  // Accented local part
            '测试@example.com',   // Chinese local part
        ];

        foreach ($emailsThatFailOnAsciiButPassOnUnicode as $email) {
            $this->fails(
                Rule::email()->withNativeValidation(),
                $email,
                ["The " . self::ATTRIBUTE_REPLACED . " must be a valid email address."],
            );

            $this->passes(
                Rule::email()->withNativeValidation(true),
                $email,
            );
        }

        /**
         * Emails that fail both ASCII and Unicode modes
         */
        $emailsThatFailOnBoth = [
            'test@üñîçødé.com',  // Unicode domain
            'user@domain..com',     // Double dots in domain
            'test@.example.com',    // Domain starts with a dot
            'username@domain-with-hyphen-.com',
            'пример@пример.рф',
            '例子@例子.公司',
            'name@123.123.123.123',
        ];

        foreach ($emailsThatFailOnBoth as $email) {
            $this->fails(
                Rule::email()->withNativeValidation(),
                $email,
                ["The " . self::ATTRIBUTE_REPLACED . " must be a valid email address."],
            );

            $this->fails(
                Rule::email()->withNativeValidation(true),
                $email,
                ["The " . self::ATTRIBUTE_REPLACED . " must be a valid email address."],
            );
        }

        /**
         * Emails that pass both ASCII-only and Unicode modes.
         * Typically straightforward addresses with no special chars,
         * or punycode that is valid under both validations.
         */
        $emailsThatPassOnBoth = [
            'user@example.com',
            'user.name+tag@example.co.uk',
            'joe_smith@example.org',
            'user@[IPv6:2001:db8:1ff::a0b:dbd0]',
            'test@xn--bcher-kva.com', // Punycode for bücher.com
        ];

        foreach ($emailsThatPassOnBoth as $email) {
            $this->passes(
                Rule::email()->withNativeValidation(),
                $email,
            );

            $this->passes(
                Rule::email()->withNativeValidation(true),
                $email,
            );
        }
    }

    public function testNativeValidationVsRfcCompliant()
    {
        $emailsThatPassNativeFailRfc = [
           // none I could find
        ];

        foreach ($emailsThatPassNativeFailRfc as $email) {
            $this->passes(
                Rule::email()->withNativeValidation(),
                $email
            );

            $this->fails(
                Rule::email()->rfcCompliant(),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.']
            );
        }

        /**
         * Addresses that fail withNativeValidation (ASCII-only)
         * but pass rfcCompliant().
         * (Typical scenario for emails with accented or Unicode local parts.)
         */
        $emailsThatFailNativePassRfc = [
            'some(comment)@example.com',        // Comment in local part
            'tést@example.com',                 // Accented local part
            'user@üñîçødé.com',                 // Unicode domain
            'user@bücher.example',              // Unicode domain
            '"has space"@example.com',          // Quoted local part with space
            '"escaped\\\"quote"@example.com',   // Escaped quote inside quoted local part
            'test@localhost',                   // Domain without TLD
            'test@example',                     // Domain without TLD
            'пример@пример.рф',
            '例子@例子.公司',
            'name@123.123.123.123',
        ];

        foreach ($emailsThatFailNativePassRfc as $email) {
            $this->fails(
                Rule::email()->withNativeValidation(),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.']
            );

            $this->passes(
                Rule::email()->rfcCompliant(),
                $email
            );
        }

        /**
         * Addresses that pass both withNativeValidation() and rfcCompliant().
         * (Standard ASCII emails with no unusual syntax or domain issues.)
         */
        $emailsThatPassBoth = [
            'plainaddress@example.com',
            'joe.smith@example.io',
            'custom-tag+dev@example.org',
            'hyphens--@example.org',
            'underscore_name@example.co.uk',
            'underscores__@example.org',
            'user@subdomain.example.com',
            'numbers123@domain.com',
            'john-doe@some-domain.com',
            'UPPERlower@example.org',
            'dots.ok@sub.domain.io',
            'some_email+tag@domain.dev',
            'a@b.c',
            'user@xn--bcher-kva.example',    // Punycode for user@bücher.example
            'user_name+tag@example.io',      // Underscore + plus tag, standard TLD
            'UPPERCASE@EXAMPLE.IO',          // All uppercase local + domain
            'abc."test"@example.com',        // Mixed quoted/unquoted local part
            'name@[127.0.0.1]',              // Local-part with domain-literal IPv4 address
            'user@[IPv6:::1]',               // Domain-literal with unusual IPv6 short form
            'a@[IPv6:2001:db8::1]',          // Domain-literal with normal IPv6
            'user@[IPv6:2001:db8:1ff::a0b:dbd0]',
        ];

        foreach ($emailsThatPassBoth as $email) {
            $this->passes(
                Rule::email()->withNativeValidation(),
                $email
            );
            $this->passes(
                Rule::email()->rfcCompliant(),
                $email
            );
        }

        /**
         * Addresses that fail both native validation and rfcCompliant.
         * (Truly invalid syntax or domain usage.)
         */
        $emailsThatFailBoth = [
            'test@@example.com',        // Multiple @
            'user@domain..com',         // Double dots in domain
            '.leadingdot@example.com',  // Leading dot in local part
            'with\\escape@example.com', // Backslash in local part
            '@example.com',             // Missing local part
            'some)@example.com',        // Unmatched parenthesis in local part
            ' space@domain.com',        // Leading space in local part
            'user@domain:port.com',     // Colon in domain (mimics a port)
            'username@domain-with-hyphen-.com',
        ];

        foreach ($emailsThatFailBoth as $email) {
            $this->fails(
                Rule::email()->withNativeValidation(),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.']
            );
            $this->fails(
                Rule::email()->rfcCompliant(),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.']
            );
        }
    }

    public function testNativeValidationVsRfcCompliantStrict()
    {
        $emailsThatPassNativeFailStrict = [
            'abc."test"@example.com',      // Mixed quotes in local part
            'name@[127.0.0.1]',            // Local-part with domain-literal IPv4 address
            'user@[IPv6:2001:db8::1]',     // Domain-literal with normal IPv6
            'user@[IPv6:2001:db8:1ff::a0b:dbd0]',
            '"ab\\(c"@example.com',
        ];

        foreach ($emailsThatPassNativeFailStrict as $email) {
            $this->passes(
                Rule::email()->withNativeValidation(),
                $email
            );

            $this->fails(
                Rule::email()->rfcCompliant(true),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.']
            );
        }

        $emailsThatFailNativePassStrict = [
            'пример@пример.рф',
            '例子@例子.公司',
            'name@123.123.123.123',
        ];

        foreach ($emailsThatFailNativePassStrict as $email) {
            $this->fails(
                Rule::email()->withNativeValidation(),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.']
            );

            $this->passes(
                Rule::email()->rfcCompliant(true),
                $email
            );
        }

        $emailsThatPassBoth = [
            'user@example.com',
            'joe.smith+dev@example.co.uk',
            'user!#$%&\'*+/=?^_`{|}~@example.com', // Unusual valid characters in local part
        ];

        foreach ($emailsThatPassBoth as $email) {
            $this->passes(
                Rule::email()->withNativeValidation(),
                $email
            );

            $this->passes(
                Rule::email()->rfcCompliant(true),
                $email
            );
        }

        $emailsThatFailBoth = [
            'test@@example.com',          // Multiple @
            '.leadingdot@example.com',    // Leading dot in local part
            'user@domain..com',           // Double dots in domain
            'test@',                      // Missing domain
            'abc"quote@example.com',
            'some(comment)@example.com',   // Local part comment
            '"has space"@example.com',     // Quoted local part with space
            'user@domain(comment)',
            'user@[127.0.0.1(comment)]',
            'some((double))comment@example.com',
            '"test\\\"quote"@example.com',     // Escaped quote in quoted local part
            '" leading.space"@example.com',    // Leading space in quoted local part
        ];

        foreach ($emailsThatFailBoth as $email) {
            $this->fails(
                Rule::email()->withNativeValidation(),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.']
            );

            $this->fails(
                Rule::email()->rfcCompliant(true),
                $email,
                ['The ' . self::ATTRIBUTE_REPLACED . ' must be a valid email address.']
            );
        }
    }

    public function testCombiningRules()
    {
        $this->passes(
            (new Email())->rfcCompliant(strict: true)->preventSpoofing(),
            'test@example.com',
        );

        $this->passes(
            Rule::email()->rfcCompliant(strict: true)->preventSpoofing(),
            'test@example.com',
        );

        $this->fails(
            (new Email())->rfcCompliant(strict: true)->preventSpoofing()->validateMxRecord(),
            'test@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
        );

        $this->fails(
            Rule::email()->rfcCompliant(strict: true)->preventSpoofing()->validateMxRecord(),
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
            ['Please check the entered '.self::ATTRIBUTE_REPLACED.", it must be a valid email address, {$spoofingEmail} given."],
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
