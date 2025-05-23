<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationInArrayKeysTest extends TestCase
{
    public function testInArrayKeysValidation()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // Test passes when array has at least one of the specified keys
        $v = new Validator($trans, ['foo' => ['first_key' => 'bar', 'second_key' => 'baz']], ['foo' => 'in_array_keys:first_key,third_key']);
        $this->assertTrue($v->passes());

        // Test passes when array has multiple of the specified keys
        $v = new Validator($trans, ['foo' => ['first_key' => 'bar', 'second_key' => 'baz']], ['foo' => 'in_array_keys:first_key,second_key']);
        $this->assertTrue($v->passes());

        // Test fails when array doesn't have any of the specified keys
        $v = new Validator($trans, ['foo' => ['first_key' => 'bar', 'second_key' => 'baz']], ['foo' => 'in_array_keys:third_key,fourth_key']);
        $this->assertTrue($v->fails());

        // Test fails when value is not an array
        $v = new Validator($trans, ['foo' => 'not-an-array'], ['foo' => 'in_array_keys:first_key']);
        $this->assertTrue($v->fails());

        // Test fails when no keys are specified
        $v = new Validator($trans, ['foo' => ['first_key' => 'bar']], ['foo' => 'in_array_keys:']);
        $this->assertTrue($v->fails());
    }

    public function testInArrayKeysValidationWithNestedArrays()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // Test passes with nested arrays
        $v = new Validator($trans, [
            'foo' => [
                'first_key' => ['nested' => 'value'],
                'second_key' => 'baz',
            ],
        ], ['foo' => 'in_array_keys:first_key,third_key']);
        $this->assertTrue($v->passes());

        // Test with dot notation for nested arrays
        $v = new Validator($trans, [
            'foo' => [
                'first' => [
                    'nested_key' => 'value',
                ],
            ],
        ], ['foo.first' => 'in_array_keys:nested_key']);
        $this->assertTrue($v->passes());
    }

    public function testInArrayKeysValidationErrorMessage()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.in_array_keys' => 'The :attribute field must contain at least one of the following keys: :values.',
        ], 'en');

        $v = new Validator($trans, ['foo' => ['wrong_key' => 'bar']], ['foo' => 'in_array_keys:first_key,second_key']);
        $this->assertFalse($v->passes());
        $this->assertEquals(
            'The foo field must contain at least one of the following keys: first_key, second_key.',
            $v->messages()->first('foo')
        );
    }

    protected function getIlluminateArrayTranslator()
    {
        return new Translator(new ArrayLoader, 'en');
    }
}
