<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rule;
use PHPUnit\Framework\TestCase;

class ValidationRuleBuilderMethodTest extends TestCase
{
    public function testSimpleRuleBuilders()
    {
        $this->assertSame('accepted', Rule::accepted());
        $this->assertSame('active_url', Rule::activeUrl());
        $this->assertSame('base64', Rule::base64());
        $this->assertSame('bail', Rule::bail());
        $this->assertSame('boolean', Rule::boolean());
        $this->assertSame('declined', Rule::declined());
        $this->assertSame('exclude', Rule::exclude());
        $this->assertSame('filled', Rule::filled());
        $this->assertSame('hex_color', Rule::hexColor());
        $this->assertSame('json', Rule::json());
        $this->assertSame('list', Rule::list());
        $this->assertSame('mac_address', Rule::macAddress());
        $this->assertSame('missing', Rule::missing());
        $this->assertSame('nullable', Rule::nullable());
        $this->assertSame('present', Rule::present());
        $this->assertSame('prohibited', Rule::prohibited());
        $this->assertSame('required', Rule::required());
        $this->assertSame('sometimes', Rule::sometimes());
        $this->assertSame('ulid', Rule::ulid());
    }

    public function testConditionalRuleBuilders()
    {
        $this->assertSame('accepted_if:user,null', (string) Rule::acceptedIf('user', null));
        $this->assertSame('declined_if:user,foo', (string) Rule::declinedIf('user', 'foo'));
        $this->assertSame('missing_if:user,24', (string) Rule::missingIf('user', 24));
        $this->assertSame('missing_unless:user,24.5', (string) Rule::missingUnless('user', 24.5));
        $this->assertSame('present_if:user,foo', (string) Rule::presentIf('user', 'foo'));
        $this->assertSame('present_unless:user,null', (string) Rule::presentUnless('user', null));
        $this->assertSame('accepted_if:user,foo,bar', (string) Rule::acceptedIf('user', 'foo', 'bar'));
        $this->assertSame('declined_if:user,false,true', (string) Rule::declinedIf('user', false, true));
        $this->assertSame('missing_if:user,foo,bar', (string) Rule::missingIf('user', ['foo', 'bar']));
        $this->assertSame('missing_unless:user,true,false', (string) Rule::missingUnless('user', [true, false]));
        $this->assertSame('present_if:user,foo,bar', (string) Rule::presentIf('user', 'foo', 'bar'));
        $this->assertSame('present_unless:user,null,foo', (string) Rule::presentUnless('user', null, 'foo'));
    }

    public function testAdditionalRuleBuilders()
    {
        $this->assertSame('confirmed', (string) Rule::confirmed());
        $this->assertSame('confirmed:email_confirmation', (string) Rule::confirmed()->customField('email_confirmation'));
        $this->assertSame('current_password', (string) Rule::currentPassword());
        $this->assertSame('current_password:api', (string) Rule::currentPassword()->guard('api'));
        $this->assertSame('distinct', (string) Rule::distinct());
        $this->assertSame('distinct:strict', (string) Rule::distinct()->strict());
        $this->assertSame('exclude_with:foo', (string) Rule::excludeWith('foo'));
        $this->assertSame('exclude_without:foo', (string) Rule::excludeWithout('foo'));
        $this->assertSame('exclude_without:foo,bar', (string) Rule::excludeWithout('foo', 'bar'));
        $this->assertSame('in_array:foo', (string) Rule::inArray('foo'));
        $this->assertSame('in_array_keys:foo,bar', (string) Rule::inArrayKeys(['foo', 'bar']));
        $this->assertSame('in_array_keys:foo,bar', (string) Rule::inArrayKeys('foo', 'bar'));
        $this->assertSame('ip', (string) Rule::ip());
        $this->assertSame('ipv4', (string) Rule::ip()->version(4));
        $this->assertSame('ipv6', (string) Rule::ip()->version(6));
        $this->assertSame('missing_with:foo,bar', (string) Rule::missingWith(['foo', 'bar']));
        $this->assertSame('missing_with:foo,bar', (string) Rule::missingWith('foo', 'bar'));
        $this->assertSame('missing_with_all:foo,bar', (string) Rule::missingWithAll(['foo', 'bar']));
        $this->assertSame('present_with:foo,bar', (string) Rule::presentWith(['foo', 'bar']));
        $this->assertSame('present_with:foo,bar', (string) Rule::presentWith('foo', 'bar'));
        $this->assertSame('present_with_all:foo,bar', (string) Rule::presentWithAll(['foo', 'bar']));
        $this->assertSame('prohibited_if_accepted:foo', (string) Rule::prohibitedIfAccepted('foo'));
        $this->assertSame('prohibited_if_declined:foo', (string) Rule::prohibitedIfDeclined('foo'));
        $this->assertSame('prohibits:foo,bar', (string) Rule::prohibits(['foo', 'bar']));
        $this->assertSame('prohibits:foo,bar', (string) Rule::prohibits('foo', 'bar'));
        $this->assertSame('regex:/^foo$/', (string) Rule::regex('/^foo$/'));
        $this->assertSame('not_regex:/^foo$/', (string) Rule::notRegex('/^foo$/'));
        $this->assertSame('required_array_keys:foo,bar', (string) Rule::requiredArrayKeys(['foo', 'bar']));
        $this->assertSame('required_array_keys:foo,bar', (string) Rule::requiredArrayKeys('foo', 'bar'));
        $this->assertSame('required_if_accepted:foo', (string) Rule::requiredIfAccepted('foo'));
        $this->assertSame('required_if_declined:foo', (string) Rule::requiredIfDeclined('foo'));
        $this->assertSame('required_with:foo,bar', (string) Rule::requiredWith(['foo', 'bar']));
        $this->assertSame('required_with:foo,bar', (string) Rule::requiredWith('foo', 'bar'));
        $this->assertSame('required_with_all:foo,bar', (string) Rule::requiredWithAll(['foo', 'bar']));
        $this->assertSame('required_without:foo,bar', (string) Rule::requiredWithout(['foo', 'bar']));
        $this->assertSame('required_without_all:foo,bar', (string) Rule::requiredWithoutAll(['foo', 'bar']));
        $this->assertSame('required_without_all:foo,bar', (string) Rule::requiredWithoutAll('foo', 'bar'));
        $this->assertSame('timezone:per_country,US', (string) Rule::timezone(['per_country', 'US']));
        $this->assertSame('url:http,https', (string) Rule::url()->protocols(['http', 'https']));
        $this->assertSame('uuid:4', (string) Rule::uuid()->version(4));
        $this->assertSame('uuid:0', (string) Rule::uuid()->version(0));
        $this->assertSame('uuid:nil', (string) Rule::uuid()->version('nil'));
        $this->assertSame('uuid:max', (string) Rule::uuid()->version('max'));
    }
}
