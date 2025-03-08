<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Orchestra\Testbench\Concerns\CreatesApplication;
use PHPUnit\Framework\TestCase;
use TypeError;

include_once 'Enums.php';

class ValidationOneOfRuleTest extends TestCase
{
    use CreatesApplication;

    private array $rules;
    private Translator $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->getIlluminateArrayTranslator();

        $this->rules = [
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
    }

    public function testThrowsTypeErrorForInvalidInput()
    {
        $this->expectException(TypeError::class);
        $v = new Validator($this->translator, ['foo' => 'not an array'], ['foo' => Rule::oneOf($this->rules)]);
        $v->validate();
    }

    public function testValidatesSuccessfullyWithKey2AndValidP2()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => ArrayKeysBacked::key_2->value,
            'p2' => 'http://localhost:8000/v1',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertTrue($validator->passes());
    }

    public function testFailsOnMissingP1()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p2' => 'http://localhost:8000/v1',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertFalse($validator->passes());
    }

    public function testFailsWhenRequiredP2IsMissingForKey3()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => ArrayKeysBacked::key_3->value,
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertFalse($validator->passes());
    }

    public function testValidatesSuccessfullyWithKey3AndP2AsString()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => ArrayKeysBacked::key_3->value,
            'p2' => 'is a string',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertTrue($validator->passes());
    }

    public function testFailsWithInvalidP1Value()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => 'invalid_key',
            'p2' => 'valid value',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertFalse($validator->passes());
    }

    public function testFailsWithInvalidURLForP2()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => ArrayKeysBacked::key_2->value,
            'p2' => 'not_a_valid_url',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertFalse($validator->passes());
    }

    public function testFailsWhenP3IsRequiredButInvalid()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => ArrayKeysBacked::key_1->value,
            'p2' => 'required_value',
            'p3' => 'invalid_url',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertFalse($validator->passes());
    }

    public function testPassesWhenP3IsValidHttpURL()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => ArrayKeysBacked::key_1->value,
            'p2' => 'required_value',
            'p3' => 'http://example.com',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertTrue($validator->passes());
    }

    public function testPassesWithOptionalP4Field()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => ArrayKeysBacked::key_1->value,
            'p2' => 'required_value',
            'p3' => 'http://example.com',
            'p4' => 'optional_value',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertTrue($validator->passes());
    }

    public function testPassesWithoutOptionalP4Field()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => ArrayKeysBacked::key_1->value,
            'p2' => 'required_value',
            'p3' => 'https://example.com',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertTrue($validator->passes());
    }

    public function testFailsWithNonNumericP2ForKey4()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => StringStatus::pending->value,
            'p2' => 'not_a_number',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertFalse($validator->passes());
    }

    public function testPassesWithValidNumericP2ForKey4()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => StringStatus::pending->value,
            'p2' => 12345,
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertTrue($validator->passes());
    }

    public function testFailsWithInvalidEmailForKey5()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => StringStatus::done->value,
            'p2' => 'not_an_email',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertFalse($validator->passes());
    }

    public function testPassesWithValidEmailForKey5()
    {
        $validator = new Validator($this->translator, ['foo' => [
            'p1' => StringStatus::done->value,
            'p2' => 'test@example.com',
        ]], ['foo' => Rule::oneOf($this->rules)]);
        $this->assertTrue($validator->passes());
    }

    private function getIlluminateArrayTranslator(): Translator
    {
        return new Translator(new ArrayLoader, 'en');
    }
}
