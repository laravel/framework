<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Support\Facades\Facade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;
use TypeError;

include_once 'Enums.php';

class ValidationAnyOfRuleTest extends TestCase
{
    private array $ruleSet1;
    private array $nestedRules;

    public function testThrowsTypeErrorForInvalidInput()
    {
        $this->expectException(TypeError::class);
        $v = new Validator(resolve('translator'), ['foo' => 'not an array'], ['foo' => Rule::anyOf($this->ruleSet1)]);
        $v->validate();
    }

    public function testValidatesPossibleNesting()
    {
        $validator = new Validator(resolve('translator'), ['foo' => [
            'p1' => [
                'p2' => 'a_string',
                'p3' => [
                    'p4' => 'a_string',
                ],
            ],
        ]], ['foo' => Rule::anyOf($this->nestedRules)]);
        $this->assertTrue($validator->passes());
    }

    public function testValidatesSuccessfullyWithKey2AndValidP2()
    {
        $validator = new Validator(resolve('translator'), ['foo' => [
            'p1' => ArrayKeysBacked::key_2->value,
            'p2' => 'http://localhost:8000/v1',
        ]], ['foo' => Rule::anyOf($this->ruleSet1)]);
        $this->assertTrue($validator->passes());
    }

    public function testFailsOnMissingP1()
    {
        $validator = new Validator(resolve('translator'), ['foo' => [
            'p2' => 'http://localhost:8000/v1',
        ]], ['foo' => Rule::anyOf($this->ruleSet1)]);
        $this->assertFalse($validator->passes());
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

        $this->ruleSet1 = [
            [
                'p1' => ['required', Rule::in([ArrayKeysBacked::key_1])],
                'p2' => ['required'],
                'p3' => ['required', 'url:http,https'],
                'p4' => ['sometimes', 'required'],
            ],
            [
                'p1' => ['required', Rule::in([ArrayKeysBacked::key_2])],
                'p2' => ['required', 'url:http,https'],
            ],
            [
                'p1' => ['required', Rule::in([ArrayKeysBacked::key_3])],
                'p2' => ['required'],
            ],
            [
                'p1' => ['required', Rule::in([StringStatus::pending])],
                'p2' => ['required', 'numeric'],
                'p3' => ['nullable', 'string'],
            ],
            [
                'p1' => ['required', Rule::in([StringStatus::done])],
                'p2' => ['required', 'email'],
                'p3' => ['nullable', 'alpha'],
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

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }
}
