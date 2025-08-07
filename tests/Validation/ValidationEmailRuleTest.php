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
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\TestWith;
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
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email(),
            'foo',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Email::default(),
            12345,
            [Email::class]
        );

        $this->fails(
            Rule::email(),
            12345,
            [Email::class]
        );

        $this->passes(
            Email::default(),
            'taylor@laravel.com'
        );

        $this->passes(
            Rule::email(),
            'taylor@laravel.com'
        );

        $this->passes(
            Rule::email(),
            ['taylor@laravel.com'],
        );

        $this->passes(
            Email::default(),
            ['taylor@laravel.com'],
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
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->rfcCompliant(strict: true),
            $emailThatPassesNonStrictButFailsInStrict,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            (new Email())->rfcCompliant(strict: true),
            $emailThatFailsBothNonStrictButFailsInStrict,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->rfcCompliant(strict: true),
            $emailThatFailsBothNonStrictButFailsInStrict,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->passes(
            (new Email())->rfcCompliant(strict: true),
            $emailThatPassesBothNonStrictAndInStrict
        );

        $this->passes(
            Rule::email()->rfcCompliant(strict: true),
            $emailThatPassesBothNonStrictAndInStrict
        );
    }

    #[RequiresPhpExtension('intl')]
    public function testValidateMxRecord()
    {
        $this->fails(
            (new Email())->validateMxRecord(),
            'plainaddress@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->validateMxRecord(),
            'plainaddress@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->passes(
            (new Email())->validateMxRecord(),
            'taylor@laravel.com'
        );

        $this->passes(
            Rule::email()->validateMxRecord(),
            'taylor@laravel.com'
        );
    }

    public function testPreventSpoofing()
    {
        $this->fails(
            (new Email())->preventSpoofing(),
            'admin@examрle.com',// Contains a Cyrillic 'р' (U+0440), not a Latin 'p'
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->preventSpoofing(),
            'admin@examрle.com',// Contains a Cyrillic 'р' (U+0440), not a Latin 'p'
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $spoofingEmail = 'admin@exam'."\u{0440}".'le.com';
        $this->fails(
            (new Email())->preventSpoofing(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->preventSpoofing(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->passes(
            (new Email())->preventSpoofing(),
            'admin@example.com'
        );

        $this->passes(
            Rule::email()->preventSpoofing(),
            'admin@example.com'
        );

        $this->passes(
            (new Email())->preventSpoofing(),
            'test👨‍💻@domain.com'
        );

        $this->passes(
            Rule::email()->preventSpoofing(),
            'test👨‍💻@domain.com'
        );
    }

    public function testWithNativeValidation()
    {
        $this->fails(
            (new Email())->withNativeValidation(),
            'tést@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->withNativeValidation(),
            'tést@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->passes(
            (new Email())->withNativeValidation(),
            'admin@example.com'
        );

        $this->passes(
            Rule::email()->withNativeValidation(),
            'admin@example.com'
        );
    }

    public function testWithNativeValidationAllowUnicode()
    {
        $this->fails(
            (new Email())->withNativeValidation(allowUnicode: true),
            'invalid.@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->withNativeValidation(allowUnicode: true),
            'invalid.@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->passes(
            (new Email())->withNativeValidation(allowUnicode: true),
            'tést@domain.com'
        );

        $this->passes(
            Rule::email()->withNativeValidation(allowUnicode: true),
            'tést@domain.com'
        );

        $this->passes(
            (new Email())->withNativeValidation(allowUnicode: true),
            'admin@example.com'
        );

        $this->passes(
            Rule::email()->withNativeValidation(allowUnicode: true),
            'admin@example.com'
        );
    }

    public function testRfcCompliantNonStrict()
    {
        $this->fails(
            (new Email())->rfcCompliant(),
            'invalid.@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->rfcCompliant(),
            'invalid.@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            (new Email())->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->passes(
            (new Email())->rfcCompliant(),
            'admin@example.com'
        );

        $this->passes(
            Rule::email()->rfcCompliant(),
            'admin@example.com'
        );

        $this->passes(
            (new Email())->rfcCompliant(),
            'tést@domain.com'
        );

        $this->passes(
            Rule::email()->rfcCompliant(),
            'tést@domain.com'
        );
    }

    public function testNoDots()
    {
        $this->fails(
            (new Email())->noDots(),
            'user.name@gmail.com',
            ['Gmail addresses with dots are not allowed.']
        );

        $this->fails(
            Rule::email()->noDots(),
            'user.name@gmail.com',
            ['Gmail addresses with dots are not allowed.']
        );

        $this->fails(
            (new Email())->noDots(),
            'u.s.e.r@googlemail.com',
            ['Gmail addresses with dots are not allowed.']
        );

        $this->passes(
            (new Email())->noDots(),
            'username@gmail.com'
        );

        $this->passes(
            (new Email())->noDots(),
            'user.name@yahoo.com'  // Only Gmail affected
        );

        $this->passes(
            Rule::email()->noDots(),
            'user.name@yahoo.com'
        );
    }

    public function testNoAliases()
    {
        $this->fails(
            (new Email())->noAliases(),
            'user+test@gmail.com',
            ['Email aliases are not allowed.']
        );

        $this->fails(
            Rule::email()->noAliases(),
            'user+test@gmail.com',
            ['Email aliases are not allowed.']
        );

        $this->fails(
            (new Email())->noAliases(),
            'user..name@gmail.com',
            ['Email aliases are not allowed.']
        );

        $this->fails(
            (new Email())->noAliases(),
            '.username@gmail.com',
            ['Email aliases are not allowed.']
        );

        $this->fails(
            (new Email())->noAliases(),
            'username.@gmail.com',
            ['Email aliases are not allowed.']
        );

        $this->passes(
            (new Email())->noAliases(),
            'user@gmail.com'
        );

        $this->passes(
            Rule::email()->noAliases(),
            'validuser@gmail.com'
        );
    }

    public function testTrustedDomains()
    {
        $this->passes(
            (new Email())->trustedDomains(),
            'user@gmail.com'
        );

        $this->passes(
            Rule::email()->trustedDomains(),
            'user@yahoo.com'
        );

        $this->passes(
            (new Email())->trustedDomains(),
            'user@outlook.com'
        );

        $this->fails(
            (new Email())->trustedDomains(),
            'user@untrusted.com',
            ['Please use an email from a supported provider: gmail.com, googlemail.com, outlook.com, hotmail.com, live.com, msn.com, icloud.com, me.com, mac.com, yahoo.com, yahoo.co.uk, yahoo.ca, yahoo.com.au, yahoo.de, yahoo.fr, yahoo.es, yahoo.it, ymail.com, rocketmail.com, aol.com, aim.com']
        );

        $this->fails(
            Rule::email()->trustedDomains(),
            'user@example.org',
            ['Please use an email from a supported provider: gmail.com, googlemail.com, outlook.com, hotmail.com, live.com, msn.com, icloud.com, me.com, mac.com, yahoo.com, yahoo.co.uk, yahoo.ca, yahoo.com.au, yahoo.de, yahoo.fr, yahoo.es, yahoo.it, ymail.com, rocketmail.com, aol.com, aim.com']
        );
    }

    public function testCustomTrustedDomains()
    {
        $this->passes(
            (new Email())->trustedDomains(['custom.com', 'trusted.org']),
            'user@custom.com'
        );

        $this->passes(
            Rule::email()->trustedDomains(['custom.com', 'trusted.org']),
            'user@trusted.org'
        );

        $this->fails(
            (new Email())->trustedDomains(['custom.com', 'trusted.org']),
            'user@gmail.com',  // Not in custom list
            ['Please use an email from a supported provider: custom.com, trusted.org']
        );

        $this->fails(
            Rule::email()->trustedDomains(['custom.com']),
            'user@untrusted.com',
            ['Please use an email from a supported provider: custom.com']
        );
    }

    #[TestWith(['user@10minutemail.com'])]
    #[TestWith(['user@guerrillamail.com'])]
    #[TestWith(['user@tempmail.org'])]
    #[TestWith(['user@yopmail.com'])]
    #[TestWith(['user@mailinator.com'])]
    #[TestWith(['user@temp-mail.org'])]
    #[TestWith(['user@throwaway.email'])]
    #[TestWith(['user@getnada.com'])]
    #[TestWith(['user@maildrop.cc'])]
    #[TestWith(['user@sharklasers.com'])]
    public function testNoDisposable($email)
    {
        $this->fails(
            (new Email())->noDisposable(),
            $email,
            ['Temporary or disposable email services are not allowed.']
        );

        $this->fails(
            Rule::email()->noDisposable(),
            $email,
            ['Temporary or disposable email services are not allowed.']
        );
    }

    public function testNoDisposablePassesValidEmails()
    {
        $this->passes(
            (new Email())->noDisposable(),
            'user@gmail.com'
        );

        $this->passes(
            Rule::email()->noDisposable(),
            'user@yahoo.com'
        );
    }

    #[TestWith(['user@simplelogin.io'])]
    #[TestWith(['user@anonaddy.com'])]
    #[TestWith(['user@relay.firefox.com'])]
    #[TestWith(['user@hide-my-email.com'])]
    #[TestWith(['user@duckduckgo.com'])]
    public function testNoForwarding($email)
    {
        $this->fails(
            (new Email())->noForwarding(),
            $email,
            ['Email forwarding services are not allowed.']
        );

        $this->fails(
            Rule::email()->noForwarding(),
            $email,
            ['Email forwarding services are not allowed.']
        );
    }

    public function testNoForwardingPassesValidEmails()
    {
        $this->passes(
            (new Email())->noForwarding(),
            'user@gmail.com'
        );

        $this->passes(
            Rule::email()->noForwarding(),
            'user@outlook.com'
        );
    }

    #[TestWith(['a12345678@gmail.com'])]      // Single letter + many numbers
    #[TestWith(['test123@gmail.com'])]        // Test emails
    #[TestWith(['temp@gmail.com'])]           // Temp emails
    #[TestWith(['noreply@gmail.com'])]        // No-reply emails
    #[TestWith(['admin@gmail.com'])]          // Admin emails
    #[TestWith(['support@gmail.com'])]        // Support emails
    #[TestWith(['1234567890@gmail.com'])]     // Only numbers
    public function testNoSuspiciousPatterns($email)
    {
        $this->fails(
            (new Email())->noSuspiciousPatterns(),
            $email,
            ['This email format appears to be temporary or invalid.']
        );

        $this->fails(
            Rule::email()->noSuspiciousPatterns(),
            $email,
            ['This email format appears to be temporary or invalid.']
        );
    }

    public function testNoSuspiciousPatternsPassesValidEmails()
    {
        $this->passes(
            (new Email())->noSuspiciousPatterns(),
            'validuser@gmail.com'
        );

        $this->passes(
            Rule::email()->noSuspiciousPatterns(),
            'john.doe@gmail.com'
        );

        $this->passes(
            (new Email())->noSuspiciousPatterns(),
            'businessemail@company.com'
        );
    }

    public function testStrictAdvanced()
    {
        $rule = (new Email())->strictAdvanced();
        $ruleViaHelper = Rule::email()->strictAdvanced();

        // Should block Gmail dots
        $this->fails(
            $rule,
            'user.name@gmail.com',
            ['Gmail addresses with dots are not allowed.']
        );

        // Should block aliases
        $this->fails(
            $ruleViaHelper,
            'user+test@gmail.com',
            ['Email aliases are not allowed.']
        );

        // Should block disposable
        $this->fails(
            $rule,
            'user@10minutemail.com',
            ['Temporary or disposable email services are not allowed.']
        );

        // Should block forwarding
        $this->fails(
            $ruleViaHelper,
            'user@simplelogin.io',
            ['Email forwarding services are not allowed.']
        );

        // Should block non-trusted
        $this->fails(
            $rule,
            'user@untrusted.com',
            ['Please use an email from a supported provider: gmail.com, googlemail.com, outlook.com, hotmail.com, live.com, msn.com, icloud.com, me.com, mac.com, yahoo.com, yahoo.co.uk, yahoo.ca, yahoo.com.au, yahoo.de, yahoo.fr, yahoo.es, yahoo.it, ymail.com, rocketmail.com, aol.com, aim.com']
        );

        // Should block suspicious
        $this->fails(
            $ruleViaHelper,
            'test123@gmail.com',
            ['This email format appears to be temporary or invalid.']
        );

        // Should allow valid
        $this->passes(
            $rule,
            'validuser@gmail.com'
        );

        $this->passes(
            $ruleViaHelper,
            'john.doe@yahoo.com'
        );
    }

    public function testChainingMethods()
    {
        $rule = (new Email())
            ->noDots()
            ->noAliases()
            ->trustedDomains();

        $this->fails(
            $rule,
            'user.name@gmail.com',
            ['Gmail addresses with dots are not allowed.']
        );

        $this->fails(
            $rule,
            'user+test@gmail.com',
            ['Email aliases are not allowed.']
        );

        $this->fails(
            $rule,
            'user@untrusted.com',
            ['Please use an email from a supported provider: gmail.com, googlemail.com, outlook.com, hotmail.com, live.com, msn.com, icloud.com, me.com, mac.com, yahoo.com, yahoo.co.uk, yahoo.ca, yahoo.com.au, yahoo.de, yahoo.fr, yahoo.es, yahoo.it, ymail.com, rocketmail.com, aol.com, aim.com']
        );

        $this->passes(
            $rule,
            'validuser@gmail.com'
        );
    }

    public function testBackwardCompatibility()
    {
        // Traditional email validation should still work
        $rule = new Email;

        $this->passes($rule, 'user.name@gmail.com');     // Dots allowed by default
        $this->passes($rule, 'user+alias@gmail.com');    // Plus allowed by default
        $this->passes($rule, 'user@anydomain.com');      // Any domain by default
        $this->passes($rule, 'user@10minutemail.com');   // Disposable allowed by default
        $this->passes($rule, 'user@simplelogin.io');     // Forwarding allowed by default
        $this->passes($rule, 'test123@gmail.com');       // Suspicious allowed by default
    }

    public function testConditionalValidation()
    {
        $emailWithDots = 'user.name@gmail.com';

        // Should pass when condition is false
        $rule = (new Email())->when(false, function ($email) {
            return $email->noDots();
        });

        $this->passes($rule, $emailWithDots);

        // Should fail when condition is true
        $rule = (new Email())->when(true, function ($email) {
            return $email->noDots();
        });

        $this->fails(
            $rule,
            $emailWithDots,
            ['Gmail addresses with dots are not allowed.']
        );
    }

    // End of Advanced Email Validation Tests

    #[TestWith(['"has space"@example.com'])]             // Quoted local part with space
    #[TestWith(['some(comment)@example.com'])]            // Comment in local part
    #[TestWith(['abc."test"@example.com'])]               // Mixed quoted/unquoted local part
    #[TestWith(['"escaped\\\"quote"@example.com'])]       // Escaped quote inside quoted local part
    #[TestWith(['test@example'])]                         // Domain without TLD
    #[TestWith(['test@localhost'])]                       // Domain without TLD
    #[TestWith(['name@[127.0.0.1]'])]                     // Local-part with domain-literal IPv4 address
    #[TestWith(['user@[IPv6:::1]'])]                      // Domain-literal with unusual IPv6 short form
    #[TestWith(['a@[IPv6:2001:db8::1]'])]                 // Domain-literal with normal IPv6
    #[TestWith(['user@[IPv6:::]'])]                       // invalid shorthand IPv6
    #[TestWith(['"ab\\(c"@example.com'])]
    public function testEmailsThatPassOnRfcCompliantButFailOnStrict($email)
    {
        $this->passes(
            Rule::email()->rfcCompliant(),
            $email
        );

        $this->fails(
            Rule::email()->rfcCompliant(strict: true),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );
    }

    #[TestWith(['plainaddress@example.com'])]
    #[TestWith(['joe.smith@example.io'])]
    #[TestWith(['custom-tag+dev@example.org'])]
    #[TestWith(['hyphens--@example.org'])]
    #[TestWith(['underscore_name@example.co.uk'])]
    #[TestWith(['underscores__@example.org'])]
    #[TestWith(['user@subdomain.example.com'])]
    #[TestWith(['numbers123@domain.com'])]
    #[TestWith(['john-doe@some-domain.com'])]
    #[TestWith(['UPPERlower@example.org'])]
    #[TestWith(['dots.ok@sub.domain.io'])]
    #[TestWith(['some_email+tag@domain.dev'])]
    #[TestWith(['a@b.c'])]
    #[TestWith(['user@xn--bcher-kva.example'])]
    #[TestWith(['user@bücher.example'])]
    public function testEmailsThatPassOnBothRfcCompliantAndStrict($email)
    {
        $this->passes(
            Rule::email()->rfcCompliant(),
            $email
        );

        $this->passes(
            Rule::email()->rfcCompliant(strict: true),
            $email
        );
    }

    #[TestWith(['invalid.@example.com'])]
    #[TestWith(['invalid@.example.com'])]
    #[TestWith(['.invalid@example.com'])]
    #[TestWith(['invalid@example.com.'])]
    #[TestWith(['some..dots@example.com'])]
    #[TestWith(['username@sub..example.com'])]
    #[TestWith(['test@example..com'])]
    #[TestWith(['test@@example.com'])]
    #[TestWith(['test👨‍💻@domain.com'])]
    #[TestWith(['username@domain-with-hyphen-.com'])]
    #[TestWith(['()<>[]:,;@example.com'])]
    #[TestWith(['@example.com'])]
    #[TestWith(['[test]@example.com'])]
    #[TestWith(['user@example.com:3000'])]
    #[TestWith(['"unescaped"quote@example.com'])]
    #[TestWith(['https://example.com'])]
    #[TestWith(['with\\escape@example.com'])]
    public function testEmailsThatFailOnBothRfcCompliantAndStrict($email)
    {
        $this->fails(
            Rule::email()->rfcCompliant(),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->rfcCompliant(strict: true),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );
    }

    #[TestWith(['plainaddress@example.com'])]       // Simple valid address
    #[TestWith(['joe.smith@example.io'])]           // Dotted local part with TLD
    #[TestWith(['custom-tag+dev@example.org'])]     // Plus tag in local part
    #[TestWith(['hyphens--@example.org'])]          // Hyphens in local part
    #[TestWith(['underscore_name@example.co.uk'])]  // Underscore in local part
    #[TestWith(['underscores__@example.org'])]      // Double underscores in local part
    #[TestWith(['user@subdomain.example.com'])]     // Subdomain in domain part
    #[TestWith(['numbers123@domain.com'])]          // Numbers in local part
    #[TestWith(['john-doe@some-domain.com'])]       // Hyphenated domain
    #[TestWith(['UPPERlower@example.org'])]         // Mixed case local part
    #[TestWith(['dots.ok@sub.domain.io'])]          // Dots in local and subdomain
    #[TestWith(['some_email+tag@domain.dev'])]      // Email with plus tag and underscore
    #[TestWith(['a@b.c'])]                          // Minimal email
    #[TestWith(['user@xn--bcher-kva.example'])]     // Punycode domain (bücher)
    #[TestWith(['user@bücher.example'])]            // Unicode domain
    public function testEmailsThatPassOnBothRfcCompliantAndRfcCompliantStrict($email)
    {
        $this->passes(
            Rule::email()->rfcCompliant(),
            $email
        );

        $this->passes(
            Rule::email()->rfcCompliant(strict: true),
            $email
        );
    }

    #[TestWith(['déjà@example.com'])]
    #[TestWith(['测试@example.com'])]
    public function testEmailsThatFailWithNativeValidationAsciiPassUnicode($email)
    {
        $this->fails(
            Rule::email()->withNativeValidation(),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->passes(
            Rule::email()->withNativeValidation(allowUnicode: true),
            $email
        );
    }

    #[TestWith(['test@üñîçødé.com'])]                   // Unicode domain
    #[TestWith(['user@domain..com'])]                   // Double dots in domain
    #[TestWith(['test@.example.com'])]                  // Domain starts with a dot
    #[TestWith(['username@domain-with-hyphen-.com'])]
    #[TestWith(['пример@пример.рф'])]                   // Cyrillic domain
    #[TestWith(['例子@例子.公司'])]                       // Chinese domain
    #[TestWith(['name@123.123.123.123'])]               // Numeric domain
    public function testEmailsThatFailOnBothWithNativeValidationAsciiAndUnicode($email)
    {
        $this->fails(
            Rule::email()->withNativeValidation(),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->withNativeValidation(allowUnicode: true),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );
    }

    #[TestWith(['user@example.com'])]
    #[TestWith(['user.name+tag@example.co.uk'])]
    #[TestWith(['joe_smith@example.org'])]
    #[TestWith(['user@[IPv6:2001:db8:1ff::a0b:dbd0]'])]
    #[TestWith(['test@xn--bcher-kva.com'])] // Punycode for bücher.com
    public function testEmailsThatPassBothWithNativeValidationAsciiAndUnicode($email)
    {
        $this->passes(
            Rule::email()->withNativeValidation(),
            $email
        );

        $this->passes(
            Rule::email()->withNativeValidation(allowUnicode: true),
            $email
        );
    }

    #[TestWith(['some(comment)@example.com'])]      // Comment in local part
    #[TestWith(['tést@example.com'])]               // Accented local part
    #[TestWith(['user@üñîçødé.com'])]               // Unicode domain
    #[TestWith(['user@bücher.example'])]            // Unicode domain
    #[TestWith(['"has space"@example.com'])]        // Quoted local part with space
    #[TestWith(['"escaped\\\"quote"@example.com'])] // Escaped quote inside quoted local part
    #[TestWith(['test@localhost'])]                 // Domain without TLD
    #[TestWith(['test@example'])]                   // Domain without TLD
    #[TestWith(['пример@пример.рф'])]               // Cyrillic local and domain
    #[TestWith(['例子@例子.公司'])]                   // Chinese local and domain
    #[TestWith(['name@123.123.123.123'])]           // Numeric domain
    public function testEmailsThatFailWithNativeValidationAsciiPassRfcCompliant($email)
    {
        $this->fails(
            Rule::email()->withNativeValidation(),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->passes(
            Rule::email()->rfcCompliant(),
            $email
        );
    }

    #[TestWith(['plainaddress@example.com'])]           // Simple valid address
    #[TestWith(['joe.smith@example.io'])]               // Dot in local part
    #[TestWith(['custom-tag+dev@example.org'])]         // Plus tag in local part
    #[TestWith(['hyphens--@example.org'])]              // Double hyphen in local part
    #[TestWith(['underscore_name@example.co.uk'])]      // Underscore in local part
    #[TestWith(['underscores__@example.org'])]          // Double underscores in local part
    #[TestWith(['user@subdomain.example.com'])]         // Subdomain in domain
    #[TestWith(['numbers123@domain.com'])]              // Numbers in local part
    #[TestWith(['john-doe@some-domain.com'])]           // Hyphen in domain
    #[TestWith(['UPPERlower@example.org'])]             // Mixed-case local part
    #[TestWith(['dots.ok@sub.domain.io'])]              // Subdomain with dot in local part
    #[TestWith(['some_email+tag@domain.dev'])]          // Underscore and tag in local part
    #[TestWith(['a@b.c'])]                              // Minimal valid address
    #[TestWith(['user@xn--bcher-kva.example'])]         // Punycode domain (bücher.example)
    #[TestWith(['user_name+tag@example.io'])]           // Underscore with tag
    #[TestWith(['UPPERCASE@EXAMPLE.IO'])]               // All uppercase local and domain
    #[TestWith(['abc."test"@example.com'])]             // Mixed quoted/unquoted local part
    #[TestWith(['name@[127.0.0.1]'])]                   // IPv4 domain literal
    #[TestWith(['user@[IPv6:::1]'])]                    // IPv6 domain with unusual short form
    #[TestWith(['a@[IPv6:2001:db8::1]'])]               // IPv6 domain normal form
    #[TestWith(['user@[IPv6:2001:db8:1ff::a0b:dbd0]'])] // Fully expanded IPv6
    public function testEmailsThatPassWithNativeValidationAndRfcCompliant($email)
    {
        $this->passes(
            Rule::email()->withNativeValidation(),
            $email
        );

        $this->passes(
            Rule::email()->rfcCompliant(),
            $email
        );
    }

    #[TestWith(['test@@example.com'])]                  // Multiple @ symbols
    #[TestWith(['user@domain..com'])]                   // Double dots in domain
    #[TestWith(['.leadingdot@example.com'])]            // Leading dot in local part
    #[TestWith(['with\\escape@example.com'])]           // Backslash in local part
    #[TestWith(['@example.com'])]                       // Missing local part
    #[TestWith(['some)@example.com'])]                  // Unmatched parenthesis in local part
    #[TestWith([' space@domain.com'])]                  // Leading space in local part
    #[TestWith(['user@domain:port.com'])]               // Colon in domain (mimics a port)
    #[TestWith(['username@domain-with-hyphen-.com'])]   // Trailing hyphen in domain
    public function testEmailsThatFailWithNativeValidationAndRfcCompliant($email)
    {
        $this->fails(
            Rule::email()->withNativeValidation(),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->rfcCompliant(),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );
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
                ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
            );
        }
    }

    #[TestWith(['abc."test"@example.com'])]             // Mixed quotes in local part
    #[TestWith(['name@[127.0.0.1]'])]                   // Local-part with domain-literal IPv4 address
    #[TestWith(['user@[IPv6:2001:db8::1]'])]            // Domain-literal with normal IPv6
    #[TestWith(['user@[IPv6:2001:db8:1ff::a0b:dbd0]'])] // Domain-literal with full IPv6 address
    #[TestWith(['"ab\\(c"@example.com'])]               // Quoted local part with escaped character
    public function testEmailsThatPassNativeValidationFailRfcCompliantStrict($email)
    {
        $this->passes(
            Rule::email()->withNativeValidation(),
            $email
        );

        $this->fails(
            Rule::email()->rfcCompliant(true),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );
    }

    #[TestWith(['пример@пример.рф'])]       // Unicode domain in Cyrillic script
    #[TestWith(['例子@例子.公司'])]           // Unicode domain in Chinese script
    #[TestWith(['name@123.123.123.123'])]   // IP address in domain part
    public function testEmailsThatFailNativeValidationPassRfcCompliantStrict($email)
    {
        $this->fails(
            Rule::email()->withNativeValidation(),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->passes(
            Rule::email()->rfcCompliant(true),
            $email
        );
    }

    #[TestWith(['user@example.com'])]                       // Simple, valid email
    #[TestWith(['joe.smith+dev@example.co.uk'])]            // Plus-tagged email with subdomain TLD
    #[TestWith(['user!#$%&\'*+/=?^_`{|}~@example.com'])]    // Unusual valid characters in local part
    public function testEmailsThatPassBothNativeValidationAndRfcCompliantStrict($email)
    {
        $this->passes(
            Rule::email()->withNativeValidation(),
            $email
        );

        $this->passes(
            Rule::email()->rfcCompliant(true),
            $email
        );
    }

    #[TestWith(['test@@example.com'])]                  // Multiple @
    #[TestWith(['.leadingdot@example.com'])]            // Leading dot in local part
    #[TestWith(['user@domain..com'])]                   // Double dots in domain
    #[TestWith(['test@'])]                              // Missing domain
    #[TestWith(['abc"quote@example.com'])]              // Unescaped quote in local part
    #[TestWith(['some(comment)@example.com'])]          // Local part comment
    #[TestWith(['"has space"@example.com'])]            // Quoted local part with space
    #[TestWith(['user@domain(comment)'])]               // Comment in domain
    #[TestWith(['user@[127.0.0.1(comment)]'])]          // Comment in domain-literal IPv4 address
    #[TestWith(['some((double))comment@example.com'])]  // Nested comment in local part
    #[TestWith(['"test\\\"quote"@example.com'])]        // Escaped quote in quoted local part
    #[TestWith(['" leading.space"@example.com'])]       // Leading space in quoted local part
    public function testEmailsThatFailBothNativeValidationAndRfcCompliantStrict($email)
    {
        $this->fails(
            Rule::email()->withNativeValidation(),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->rfcCompliant(true),
            $email,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );
    }

    #[RequiresPhpExtension('intl')]
    public function testCombiningRules()
    {
        $this->passes(
            (new Email())->rfcCompliant(strict: true)->preventSpoofing(),
            'test@example.com'
        );

        $this->passes(
            Rule::email()->rfcCompliant(strict: true)->preventSpoofing(),
            'test@example.com'
        );

        $this->fails(
            (new Email())->rfcCompliant(strict: true)->preventSpoofing()->validateMxRecord(),
            'test@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->rfcCompliant(strict: true)->preventSpoofing()->validateMxRecord(),
            'test@example.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->passes(
            (new Email())->preventSpoofing(),
            'test👨‍💻@domain.com'
        );

        $this->passes(
            Rule::email()->preventSpoofing(),
            'test👨‍💻@domain.com'
        );

        $this->fails(
            (new Email())->preventSpoofing()->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->preventSpoofing()->rfcCompliant(),
            'test👨‍💻@domain.com',
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $spoofingEmail = 'admin@exam'."\u{0440}".'le.com';

        $this->passes(
            (new Email())->rfcCompliant(),
            $spoofingEmail
        );

        $this->passes(
            Rule::email()->rfcCompliant(),
            $spoofingEmail
        );

        $this->fails(
            (new Email())->rfcCompliant()->preventSpoofing(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            Rule::email()->rfcCompliant()->preventSpoofing(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
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
            'taylor@laravel.com'
        );

        $this->passes(
            Rule::email()->laravelEmployee(),
            'taylor@laravel.com'
        );
    }

    public function testItCanSetDefaultUsing()
    {
        $this->assertInstanceOf(Email::class, Email::default());

        $spoofingEmail = 'admin@exam'."\u{0440}".'le.com';

        $this->passes(
            Email::default(),
            $spoofingEmail
        );

        Email::defaults(function () {
            return (new Email())->preventSpoofing();
        });

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        Email::defaults(function () {
            return Rule::email()->rfcCompliant();
        });

        $this->passes(
            Email::default(),
            $spoofingEmail
        );

        Email::defaults(function () {
            return Rule::email()->preventSpoofing();
        });

        $this->fails(
            Email::default(),
            $spoofingEmail,
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
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
            ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.']
        );

        $this->fails(
            rule: Email::default(),
            values: $spoofingEmail,
            expectedMessages: ['The '.self::ATTRIBUTE_REPLACED.' must be a valid email address.'],
            customValidationMessage: 'The :attribute must be a valid email address.'
        );

        $this->fails(
            rule: Email::default(),
            values: $spoofingEmail,
            expectedMessages: ['Please check the entered '.self::ATTRIBUTE_REPLACED.", it must be a valid email address, {$spoofingEmail} given."],
            customValidationMessage: 'Please check the entered :attribute, it must be a valid email address, :input given.'
        );

        $this->fails(
            rule: Email::default(),
            values: $spoofingEmail,
            expectedMessages: ['Plain text value'],
            customValidationMessage: 'Plain text value'
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
                'validation.email_trusted_domains' => 'Please use an email from a supported provider: :domains',
                'validation.email_no_dots' => 'Gmail addresses with dots are not allowed.',
                'validation.email_no_aliases' => 'Email aliases are not allowed.',
                'validation.email_no_disposable' => 'Temporary or disposable email services are not allowed.',
                'validation.email_no_forwarding' => 'Email forwarding services are not allowed.',
                'validation.email_suspicious' => 'This email format appears to be temporary or invalid.',
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
