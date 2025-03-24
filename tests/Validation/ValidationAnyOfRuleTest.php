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

include_once 'Enums.php';

enum Validators: string
{
    case EMAIL = 'email';
    case URL = 'url';
    case IN = 'in';
}

class ValidationAnyOfRuleTest extends TestCase
{
    private array $ruleSets;
    private array $nestedRules;
    private array $nestedRules2;

    public function testBasicValidation()
    {
        $rule = ['email' => Rule::anyOf([
            ['required', 'min:20'],
            ['required', 'email'],
        ])];

        $validator = new Validator(resolve('translator'), [
            'email' => 'test@example.com',
        ], $rule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'email' => '20charstringtestvalidation',
        ], $rule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'email' => null,
        ], $rule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'email' => 'abc',
        ], $rule);
        $this->assertFalse($validator->passes());
    }

    public function testBasicStringValidation()
    {
        $rule = ['email' => Rule::anyOf([
            'required|min:20',
            'required|email',
        ])];

        $validator = new Validator(resolve('translator'), [
            'email' => 'test@example.com',
        ], $rule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'email' => '20charstringtestvalidation',
        ], $rule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'email' => null,
        ], $rule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'email' => 'abc',
        ], $rule);
        $this->assertFalse($validator->passes());
    }

    public function testBareBasicStringRuleValidation()
    {
        $rule = ['p1' => Rule::anyOf([
            ['p2' => ['required', 'min:20']],
            'required|min:20',
        ])];

        $validator = new Validator(resolve('translator'), [
            'p1' => ['p2' => '20charstringtestvalidation'],
        ], $rule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'p1' => ['p2' => 'abc'],
        ], $rule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'p1' => ['p2' => null],
        ], $rule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'p1' => null,
        ], $rule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'p1' => '20charstringtestvalidation',
        ], $rule);
        $this->assertTrue($validator->passes());
    }

    public function testEmailValidation()
    {
        $validator = new Validator(resolve('translator'), ['type_email_matches' => [
            'type' => 'email',
            'email' => 'test@example.com',
        ]], ['type_email_matches' => Rule::anyOf($this->ruleSets)]);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), ['email_is_just_a_string' => [
            'type' => 'email',
            'email' => 'invalid-email',
        ]], ['email_is_just_a_string' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), ['url_instead_of_email' => [
            'type' => 'email',
            'url' => 'https://example.com',
        ]], ['url_instead_of_email' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), ['missing_email' => [
            'type' => 'email',
        ]], ['missing_email' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());
    }

    public function testUrlValidation()
    {
        $validator = new Validator(resolve('translator'), ['type_url_matches' => [
            'type' => 'url',
            'url' => 'https://example.com',
        ]], ['type_url_matches' => Rule::anyOf($this->ruleSets)]);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), ['url_is_just_a_string' => [
            'type' => 'url',
            'url' => 'not-a-url',
        ]], ['url_is_just_a_string' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), ['email_instead_of_url' => [
            'type' => 'url',
            'email' => 'test@example.com',
        ]], ['email_instead_of_url' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), ['missing_url' => [
            'type' => 'url',
        ]], ['missing_url' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());
    }

    public function testInValidation()
    {
        $validator = new Validator(resolve('translator'), ['type_in_matches_1' => [
            'type' => 'in',
            'in' => 'key_1',
        ]], ['type_in_matches_1' => Rule::anyOf($this->ruleSets)]);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), ['type_in_matches_2' => [
            'type' => 'in',
            'in' => 'key_2',
        ]], ['type_in_matches_2' => Rule::anyOf($this->ruleSets)]);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), ['unexpected_in_value' => [
            'type' => 'in',
            'in' => 'unexpected_value',
        ]], ['unexpected_in_value' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), ['url_instead_of_in' => [
            'type' => 'in',
            'url' => 'https://example.com',
        ]], ['url_instead_of_in' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), ['missing_in' => [
            'type' => 'in',
        ]], ['missing_in' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());
    }

    public function testMissingTagValidation()
    {
        $validator = new Validator(resolve('translator'), ['invalid_tag_with_url' => [
            'type' => 'doesnt_exist',
            'url' => 'https://example.com',
        ]], ['invalid_tag_with_url' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), ['invalid_tag_with_email' => [
            'type' => 'doesnt_exist',
            'email' => 'test@example.com',
        ]], ['invalid_tag_with_email' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), ['invalid_tag_with_in' => [
            'type' => 'doesnt_exist',
            'in' => 'key_1',
        ]], ['invalid_tag_with_in' => Rule::anyOf($this->ruleSets)]);
        $this->assertFalse($validator->passes());
    }

    public function testNestedValidation()
    {
        $validator = new Validator(resolve('translator'), [
            'complete' => ['p1' => [
                'p2' => 'a_valid_string',
                'p3' => ['p4' => 'another_valid_string'],
            ]],
        ], ['complete' => Rule::anyOf($this->nestedRules)]);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'p2_is_missing' => ['p1' => [
                'p3' => ['p4' => 'valid_string'],
            ]],
        ], ['p2_is_missing' => Rule::anyOf($this->nestedRules)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'p3_is_missing' => ['p1' => [
                'p2' => 'a_valid_string',
            ]],
        ], ['p3_is_missing' => Rule::anyOf($this->nestedRules)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'p3_is_null' => ['p1' => [
                'p2' => 'a_valid_string',
                'p3' => null,
            ]],
        ], ['p3_is_null' => Rule::anyOf($this->nestedRules)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'p3_is_required_whatever_it_may_be' => ['p1' => [
                'p2' => 'a_valid_string',
                'p3' => 'is_required_whatever_it_may_be',
            ]],
        ], ['p3_is_required_whatever_it_may_be' => Rule::anyOf($this->nestedRules)]);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'p4_is_nullable' => ['p1' => [
                'p2' => 'a_valid_string',
                'p3' => ['p4' => null],
            ]],
        ], ['p4_is_nullable' => Rule::anyOf($this->nestedRules)]);
        $this->assertTrue($validator->passes());
    }

    protected function setUpRuleSets()
    {
        $this->ruleSets = [
            [
                'type' => ['required', Rule::in([Validators::EMAIL])],
                'email' => ['required', 'email:rfc'],
            ],
            [
                'type' => ['required', Rule::in([Validators::URL])],
                'url' => ['required', 'url:http,https'],
            ],
            [
                'type' => ['required', Rule::in([Validators::IN])],
                'in' => ['required', Rule::enum(ArrayKeysBacked::class)],
            ],
        ];

        $this->nestedRules = [
            ['p1' => Rule::anyOf([
                [
                    'p2' => 'required',
                    'p3' => ['required', Rule::anyOf([[
                        'p4' => ['nullable'],
                    ]])],
                ],
            ])],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::getInstance();
        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader,
                'en'
            );
        });

        Facade::setFacadeApplication($container);
        (new ValidationServiceProvider($container))->register();

        $this->setUpRuleSets();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }
}
