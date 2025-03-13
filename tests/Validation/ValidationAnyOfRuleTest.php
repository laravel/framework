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
use TypeError;

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

    public function testThrowsTypeErrorForInvalidInput()
    {
        $this->expectException(TypeError::class);
        $validator = new Validator(resolve('translator'), [
            'foo' => 'not an array',
        ], ['foo' => Rule::anyOf([[]])]);

        $validator->validate();
    }

    public function testValidEmailValidation()
    {
        $validator = new Validator(resolve('translator'), ['foo' => [
            'type' => 'email',
            'email' => 'test@example.com',
        ]], ['foo' => Rule::anyOf($this->ruleSets)]);

        $this->assertTrue($validator->passes());
    }

    public function testInvalidEmailValidation()
    {
        $validator = new Validator(resolve('translator'), ['foo' => [
            'type' => 'email',
            'email' => 'invalid-email',
        ]], ['foo' => Rule::anyOf($this->ruleSets)]);

        $this->assertFalse($validator->passes());
    }

    public function testValidUrlValidation()
    {
        $validator = new Validator(resolve('translator'), ['foo' => [
            'type' => 'url',
            'url' => 'https://example.com',
        ]], ['foo' => Rule::anyOf($this->ruleSets)]);

        $this->assertTrue($validator->passes());
    }

    public function testInvalidUrlValidation()
    {
        $validator = new Validator(resolve('translator'), ['foo' => [
            'type' => 'url',
            'url' => 'not-a-url',
        ]], ['foo' => Rule::anyOf($this->ruleSets)]);

        $this->assertFalse($validator->passes());
    }

    public function testErroneousEmailValidationOnUrlRule()
    {
        $validator = new Validator(resolve('translator'), ['foo' => [
            'type' => 'url',
            'email' => 'test@example.com',
        ]], ['foo' => Rule::anyOf($this->ruleSets)]);

        $this->assertFalse($validator->passes());
    }

    public function testValidInValidation()
    {
        $validator = new Validator(resolve('translator'), ['foo' => [
            'type' => 'in',
            'in' => 'key_1',
        ]], ['foo' => Rule::anyOf($this->ruleSets)]);

        $this->assertTrue($validator->passes());
    }

    public function testInvalidInValidation()
    {
        $validator = new Validator(resolve('translator'), ['foo' => [
            'type' => 'in',
            'in' => 'unexpected_value',
        ]], ['foo' => Rule::anyOf($this->ruleSets)]);

        $this->assertFalse($validator->passes());
    }

    public function testValidNestedValidation()
    {
        $validator = new Validator(resolve('translator'), [
            'foo' => [
                'p1' => [
                    'p2' => 'a_valid_string',
                    'p3' => [
                        'p4' => 'another_valid_string',
                    ],
                ],
            ],
        ], ['foo' => Rule::anyOf($this->nestedRules)]);

        $this->assertTrue($validator->passes());
    }

    public function testInvalidNestedValidation()
    {
        $validator = new Validator(resolve('translator'), [
            'foo' => [
                'p1' => [
                    'p2' => '', // required field left empty
                    'p3' => [
                        'p4' => 'valid_string',
                    ],
                ],
            ],
        ], ['foo' => Rule::anyOf($this->nestedRules)]);

        $this->assertFalse($validator->passes());
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
            [
                'p1' => ['required', Rule::anyOf([
                    [
                        'p2' => ['required', 'string'],
                        'p3' => ['required', Rule::anyOf([[
                            'p4' => ['nullable', 'string'],
                        ]])],
                    ],
                ])],
            ],
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
