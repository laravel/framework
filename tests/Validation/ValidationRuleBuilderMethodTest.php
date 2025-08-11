<?php

declare(strict_types=1);

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationRuleBuilderMethodTest extends TestCase
{
    public function testAlphaRule()
    {
        $this->assertSame(Rule::alpha()->__toString(), 'alpha');
        $this->assertSame(Rule::alpha()->ascii()->__toString(), 'alpha:ascii');
    }

    public function testAlphaDashRule()
    {
        $this->assertSame(Rule::alphaDash()->__toString(), 'alpha_dash');
        $this->assertSame(Rule::alphaDash()->ascii()->__toString(), 'alpha_dash:ascii');
    }

    public function testAlphaNumRule()
    {
        $this->assertSame(Rule::alphaNum()->__toString(), 'alpha_num');
        $this->assertSame(Rule::alphaNum()->ascii()->__toString(), 'alpha_num:ascii');
    }

    public function testAsciiRule()
    {
        $this->assertSame(Rule::ascii(), 'ascii');
    }

    public function testConfirmedRule()
    {
        $this->assertSame(Rule::confirmed()->__toString(), 'confirmed');
        $this->assertSame(Rule::confirmed()->customField('foo')->__toString(), 'confirmed:foo');
    }

    public function testCurrentPasswordRule()
    {
        $this->assertSame(Rule::currentPassword()->__toString(), 'current_password');
        $this->assertSame('current_password:api', Rule::currentPassword()->guard('api')->__toString());
    }

    public function testDifferentRule()
    {
        $this->assertSame(Rule::different('email')->__toString(), 'different:email');
    }

    public function testSizeRule()
    {
        $this->assertSame(Rule::size(12)->__toString(), 'size:12');
        $this->assertSame(Rule::size(12.56)->__toString(), 'size:12.56');
    }

    public function testSameRule()
    {
        $this->assertSame(Rule::same('field')->__toString(), 'same:field');
    }

    public function testCurrentPasswordRuleWithCustomGuard()
    {
        $this->assertSame(Rule::currentPassword()->guard('api')->__toString(), 'current_password:api');
    }

    public function testActiveUrlRule()
    {
        $this->assertSame(Rule::activeUrl(), 'active_url');
    }

    public function testAcceptedIfRule()
    {
        $this->assertSame(Rule::acceptedIf('user', null)->__toString(), 'accepted_if:user,null');
        $this->assertSame(Rule::acceptedIf('user', 24)->__toString(), 'accepted_if:user,24');
        $this->assertSame(Rule::acceptedIf('user', 24.56)->__toString(), 'accepted_if:user,24.56');
        $this->assertSame(Rule::acceptedIf('user', 'foo')->__toString(), 'accepted_if:user,foo');
    }

    public function testDeclinedRule()
    {
        $this->assertSame(Rule::declined(), 'declined');
    }

    public function testDoesntStartWithRule()
    {
        $this->assertSame(Rule::doesntStartWith(['foo'])->__toString(), 'doesnt_start_with:foo');
        $this->assertSame(Rule::doesntStartWith(['foo', 'bar'])->__toString(), 'doesnt_start_with:foo,bar');
    }

    public function testStartsWithRule()
    {
        $this->assertSame(Rule::startsWith(['foo'])->__toString(), 'starts_with:foo');
        $this->assertSame(Rule::startsWith(['foo', 'bar'])->__toString(), 'starts_with:foo,bar');
    }

    public function testIpRule()
    {
        $this->assertSame(Rule::ip()->__toString(), 'ip');
        $this->assertSame(Rule::ip()->version(4)->__toString(), 'ipv4');
        $this->assertSame(Rule::ip()->version(6)->__toString(), 'ipv6');

        $this->expectException(InvalidArgumentException::class);
        $this->assertSame(Rule::ip()->version(1)->__toString(), 'ipv4');
    }

    public function testUuidRule()
    {
        $this->assertSame(Rule::uuid()->__toString(), 'uuid');
        $this->assertSame(Rule::uuid()->version(3)->__toString(), 'uuid:3');
    }

    public function testDoesntEndWithRule()
    {
        $this->assertSame(Rule::doesntEndWith(['foo'])->__toString(), 'doesnt_end_with:foo');
        $this->assertSame(Rule::doesntEndWith(['foo', 'bar'])->__toString(), 'doesnt_end_with:foo,bar');
    }

    public function testJsonRule()
    {
        $this->assertSame(Rule::json(), 'json');
    }

    public function testLowercaseRule()
    {
        $this->assertSame(Rule::lowercase(), 'lowercase');
    }

    public function testUppercaseRule()
    {
        $this->assertSame(Rule::uppercase(), 'uppercase');
    }

    public function testUlidRule()
    {
        $this->assertSame(Rule::ulid(), 'ulid');
    }

    public function testEndsWithRule()
    {
        $this->assertSame(Rule::endsWith(['foo'])->__toString(), 'ends_with:foo');
        $this->assertSame(Rule::endsWith(['foo', 'bar'])->__toString(), 'ends_with:foo,bar');
    }

    public function testHexColorRule()
    {
        $this->assertSame(Rule::hexColor(), 'hex_color');
    }

    public function testDeclinedIfRule()
    {
        $this->assertSame(Rule::declinedIf('user', null)->__toString(), 'declined_if:user,null');
        $this->assertSame(Rule::declinedIf('user', 24)->__toString(), 'declined_if:user,24');
        $this->assertSame(Rule::declinedIf('user', 24.56)->__toString(), 'declined_if:user,24.56');
        $this->assertSame(Rule::declinedIf('user', 'foo')->__toString(), 'declined_if:user,foo');
    }

    public function testBooleanRule()
    {
        $this->assertSame(Rule::boolean(), 'boolean');
    }

    public function testStringRule()
    {
        $this->assertSame(Rule::string(), 'string');
    }

    public function testAcceptedRule()
    {
        $this->assertSame(Rule::accepted(), 'accepted');
    }

    public function testRequiredRule()
    {
        $this->assertSame(Rule::required(), 'required');
    }

    public function testIntegerRule()
    {
        $this->assertSame(Rule::integer(), 'integer');
    }

    public function testNullableRule()
    {
        $this->assertSame(Rule::nullable(), 'nullable');
    }

    public function testDecimalRule()
    {
        $this->assertSame(Rule::decimal(minPlaces: 2)->__toString(), 'decimal:2');
        $this->assertSame(Rule::decimal(minPlaces: 2, maxPlaces: 5)->__toString(), 'decimal:2,5');

        $this->expectException(InvalidArgumentException::class);
        Rule::decimal(minPlaces: 5, maxPlaces: 2);
    }

    public function testSometimesRule()
    {
        $this->assertSame(Rule::sometimes(), 'sometimes');
    }

    public function testUrlRule()
    {
        $this->assertSame(Rule::url()->__toString(), 'url');
        $this->assertSame(Rule::url()->protocols(['http', 'https'])->__toString(), 'url:http,https');
    }

    public function testMacAddressRule()
    {
        $this->assertSame(Rule::macAddress(), 'mac_address');
    }

    public function testRegexRule()
    {
        $this->assertSame(Rule::regex('/^.+@.+$/i')->__toString(), 'regex:/^.+@.+$/i');
    }

    public function testNotRegexRule()
    {
        $this->assertSame(Rule::notRegex('/^.+@.+$/i')->__toString(), 'not_regex:/^.+@.+$/i');
    }

    public function testMinRule()
    {
        $this->assertSame(Rule::min(12)->__toString(), 'min:12');
        $this->assertSame(Rule::min(fn (): int => 12)->__toString(), 'min:12');
    }

    public function testMaxRule()
    {
        $this->assertSame(Rule::max(12)->__toString(), 'max:12');
        $this->assertSame(Rule::max(fn (): int => 12)->__toString(), 'max:12');
    }

    public function testBetweenRule()
    {
        $this->assertSame(Rule::between(1, 10)->__toString(), 'between:1,10');
        $this->assertSame(Rule::between(0.00, 0.99)->__toString(), 'between:0,0.99');
        $this->assertSame(Rule::between(1.11, 1.99)->__toString(), 'between:1.11,1.99');
    }

    public function testExtensionsRule()
    {
        $this->assertSame(Rule::extensions(['png', 'mp3'])->__toString(), 'extensions:png,mp3');
    }

    public function testDigitsRule()
    {
        $this->assertSame(Rule::digits(12)->__toString(), 'digits:12');
    }

    public function testDigitsBetweenRule()
    {
        $this->assertSame(Rule::digitsBetween(12, 24)->__toString(), 'digits_between:12,24');
    }

    public function testMaxDigitsRule()
    {
        $this->assertSame(Rule::maxDigits(3)->__toString(), 'max_digits:3');
    }

    public function testMinDigitsRule()
    {
        $this->assertSame(Rule::minDigits(3)->__toString(), 'min_digits:3');
    }

    public function testDistinctRule()
    {
        $this->assertSame(Rule::distinct()->__toString(), 'distinct');
        $this->assertSame(Rule::distinct()->ignoreCase()->__toString(), 'distinct:ignore_case');
        $this->assertSame(Rule::distinct()->strict()->__toString(), 'distinct:strict');
    }

    public function testGreaterThanRule()
    {
        $this->assertSame(Rule::greaterThan(1)->__toString(), 'gt:1');
        $this->assertSame(Rule::greaterThan('item')->__toString(), 'gt:item');

        $this->assertSame(Rule::gt(1)->__toString(), 'gt:1');
        $this->assertSame(Rule::gt('item')->__toString(), 'gt:item');
    }

    public function testGreaterThanOrEqualRule()
    {
        $this->assertSame(Rule::greaterThanOrEqual(1)->__toString(), 'gte:1');
        $this->assertSame(Rule::greaterThanOrEqual('item')->__toString(), 'gte:item');

        $this->assertSame(Rule::gte(1)->__toString(), 'gte:1');
        $this->assertSame(Rule::gte('item')->__toString(), 'gte:item');
    }

    public function testLessThanRule()
    {
        $this->assertSame(Rule::lessThan(1)->__toString(), 'lt:1');
        $this->assertSame(Rule::lessThan('item')->__toString(), 'lt:item');

        $this->assertSame(Rule::lt(1)->__toString(), 'lt:1');
        $this->assertSame(Rule::lt('item')->__toString(), 'lt:item');
    }

    public function testLessThanOrEqualRule()
    {
        $this->assertSame(Rule::lessThanOrEqual(1)->__toString(), 'lte:1');
        $this->assertSame(Rule::lessThanOrEqual('item')->__toString(), 'lte:item');

        $this->assertSame(Rule::lte(1)->__toString(), 'lte:1');
        $this->assertSame(Rule::lte('item')->__toString(), 'lte:item');
    }

    public function testMultipleOfRule()
    {
        $this->assertSame(Rule::multipleOf(5)->__toString(), 'multiple_of:5');
        $this->assertSame(Rule::multipleOf(0.25)->__toString(), 'multiple_of:0.25');
    }

    public function testInArrayRule()
    {
        $this->assertSame(Rule::inArray('foo')->__toString(), 'in_array:foo');
    }

    public function testInArrayKeysRule()
    {
        $this->assertSame(Rule::inArrayKeys(['foo', 'bar'])->__toString(), 'in_array_keys:foo,bar');
    }

    public function testListRule()
    {
        $this->assertSame(Rule::list(), 'list');
    }

    public function testBailRule()
    {
        $this->assertSame(Rule::bail(), 'bail');
    }

    public function testPresentRule()
    {
        $this->assertSame(Rule::present(), 'present');
    }

    public function testProhibitedRule()
    {
        $this->assertSame(Rule::prohibited(), 'prohibited');
    }

    public function testFilledRule()
    {
        $this->assertSame(Rule::filled(), 'filled');
    }

    public function testDateEqualsRule()
    {
        $now = now()->toImmutable();

        $this->assertSame(Rule::date()->equals('now')->__toString(), 'date|date_equals:now');
        $this->assertSame(Rule::date()->equals('tomorrow')->__toString(), 'date|date_equals:tomorrow');
        $this->assertSame(Rule::date()->equals($now)->__toString(), 'date|date_equals:'.$now->format('Y-m-d'));
        $this->assertSame(Rule::date()->equals($now->format('H:i'))->__toString(), 'date|date_equals:'.$now->format('H:i'));
    }

    public function testTimezoneRule()
    {
        $this->assertSame(Rule::timezone()->__toString(), 'timezone');
        $this->assertSame(Rule::timezone(['Africa'])->__toString(), 'timezone:Africa');
        $this->assertSame(Rule::timezone(['per_country', 'US'])->__toString(), 'timezone:per_country,US');
    }

    public function testExcludeRule()
    {
        $this->assertSame(Rule::exclude(), 'exclude');
    }

    public function testMimeTypesRule()
    {
        $this->assertSame(Rule::mimeTypes(['video/avi', 'video/mpeg', 'video/quicktime'])->__toString(), 'mimetypes:video/avi,video/mpeg,video/quicktime');
    }

    public function testMimesRule()
    {
        $this->assertSame(Rule::mimes(['jpg', 'bmp', 'png'])->__toString(), 'mimes:jpg,bmp,png');
    }

    public function testExcludeUnlessRule()
    {
        $this->assertSame(Rule::excludeUnless('user', null)->__toString(), 'exclude_unless:user,null');
        $this->assertSame(Rule::excludeUnless('user', 24)->__toString(), 'exclude_unless:user,24');
        $this->assertSame(Rule::excludeUnless('user', 24.56)->__toString(), 'exclude_unless:user,24.56');
        $this->assertSame(Rule::excludeUnless('user', 'foo')->__toString(), 'exclude_unless:user,foo');
    }

    public function testExcludeWithRule()
    {
        $this->assertSame(Rule::excludeWith('foo')->__toString(), 'exclude_with:foo');
    }

    public function testExcludeWithoutRule()
    {
        $this->assertSame(Rule::excludeWithout('foo')->__toString(), 'exclude_without:foo');
    }

    public function testMissingRule()
    {
        $this->assertSame(Rule::missing(), 'missing');
    }

    public function testProhibitsRule()
    {
        $this->assertSame(Rule::prohibits(['foo', 'bar'])->__toString(), 'prohibits:foo,bar');
    }

    public function testMissingIfRule()
    {
        $this->assertSame(Rule::missingIf('user', null)->__toString(), 'missing_if:user,null');
        $this->assertSame(Rule::missingIf('user', 24)->__toString(), 'missing_if:user,24');
        $this->assertSame(Rule::missingIf('user', 24.56)->__toString(), 'missing_if:user,24.56');
        $this->assertSame(Rule::missingIf('user', 'foo')->__toString(), 'missing_if:user,foo');
    }

    public function testRequiredArrayKeysRule()
    {
        $this->assertSame(Rule::requiredArrayKeys(['foo', 'bar'])->__toString(), 'required_array_keys:foo,bar');
    }

    public function testMissingUnlessRule()
    {
        $this->assertSame(Rule::missingUnless('user', null)->__toString(), 'missing_unless:user,null');
        $this->assertSame(Rule::missingUnless('user', 24)->__toString(), 'missing_unless:user,24');
        $this->assertSame(Rule::missingUnless('user', 24.56)->__toString(), 'missing_unless:user,24.56');
        $this->assertSame(Rule::missingUnless('user', 'foo')->__toString(), 'missing_unless:user,foo');
    }

    public function testPresentIfRule()
    {
        $this->assertSame(Rule::presentIf('user', null)->__toString(), 'present_if:user,null');
        $this->assertSame(Rule::presentIf('user', 24)->__toString(), 'present_if:user,24');
        $this->assertSame(Rule::presentIf('user', 24.56)->__toString(), 'present_if:user,24.56');
        $this->assertSame(Rule::presentIf('user', 'foo')->__toString(), 'present_if:user,foo');
    }

    public function testProhibitedUnlessRule()
    {
        $this->assertSame(Rule::prohibitedUnless('user', null)->__toString(), 'prohibited_unless:user,null');
        $this->assertSame(Rule::prohibitedUnless('user', 24)->__toString(), 'prohibited_unless:user,24');
        $this->assertSame(Rule::prohibitedUnless('user', 24.56)->__toString(), 'prohibited_unless:user,24.56');
        $this->assertSame(Rule::prohibitedUnless('user', 'foo')->__toString(), 'prohibited_unless:user,foo');
    }

    public function testRequiredUnlessRule()
    {
        $this->assertSame(Rule::requiredUnless('user', null)->__toString(), 'required_unless:user,null');
        $this->assertSame(Rule::requiredUnless('user', 24)->__toString(), 'required_unless:user,24');
        $this->assertSame(Rule::requiredUnless('user', 24.56)->__toString(), 'required_unless:user,24.56');
        $this->assertSame(Rule::requiredUnless('user', 'foo')->__toString(), 'required_unless:user,foo');
    }

    public function testMissingWithRule()
    {
        $this->assertSame(Rule::missingWith(['foo', 'bar'])->__toString(), 'missing_with:foo,bar');
    }

    public function testMissingWithAllRule()
    {
        $this->assertSame(Rule::missingWithAll(['foo', 'bar'])->__toString(), 'missing_with_all:foo,bar');
    }

    public function testPresentUnlessRule()
    {
        $this->assertSame(Rule::presentUnless('user', null)->__toString(), 'present_unless:user,null');
        $this->assertSame(Rule::presentUnless('user', 24)->__toString(), 'present_unless:user,24');
        $this->assertSame(Rule::presentUnless('user', 24.56)->__toString(), 'present_unless:user,24.56');
        $this->assertSame(Rule::presentUnless('user', 'foo')->__toString(), 'present_unless:user,foo');
    }

    public function testPresentWithRule()
    {
        $this->assertSame(Rule::presentWith(['foo', 'bar'])->__toString(), 'present_with:foo,bar');
    }

    public function testPresentWithAllRule()
    {
        $this->assertSame(Rule::presentWithAll(['foo', 'bar'])->__toString(), 'present_with_all:foo,bar');
    }

    public function testRequiredWithRule()
    {
        $this->assertSame(Rule::requiredWith(['foo', 'bar'])->__toString(), 'required_with:foo,bar');
    }

    public function testRequiredWithAllRule()
    {
        $this->assertSame(Rule::requiredWithAll(['foo', 'bar'])->__toString(), 'required_with_all:foo,bar');
    }

    public function testRequiredWithoutRule()
    {
        $this->assertSame(Rule::requiredWithout(['foo', 'bar'])->__toString(), 'required_without:foo,bar');
    }

    public function testRequiredWithoutAllRule()
    {
        $this->assertSame(Rule::requiredWithoutAll(['foo', 'bar'])->__toString(), 'required_without_all:foo,bar');
    }

    public function testProhibitedIfAcceptedRule()
    {
        $this->assertSame(Rule::prohibitedIfAccepted(['foo', 'bar'])->__toString(), 'prohibited_if_accepted:foo,bar');
    }

    public function testProhibitedIfDeclinedRule()
    {
        $this->assertSame(Rule::prohibitedIfDeclined(['foo', 'bar'])->__toString(), 'prohibited_if_declined:foo,bar');
    }

    public function testRequiredIfAcceptedRule()
    {
        $this->assertSame(Rule::requiredIfAccepted(['foo', 'bar'])->__toString(), 'required_if_accepted:foo,bar');
    }

    public function testRequiredIfDeclinedRule()
    {
        $this->assertSame(Rule::requiredIfDeclined(['foo', 'bar'])->__toString(), 'required_if_declined:foo,bar');
    }
}
