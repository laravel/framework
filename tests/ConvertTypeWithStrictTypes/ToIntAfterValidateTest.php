<?php

declare(strict_types=1);

namespace ConvertTypeWithStrictTypes;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\TestCase;

class ToIntAfterValidateTest extends TestCase
{
    public function test_it_should_keep_integer_as_string_after_validation_with_strict_types()
    {
        $data = ['page' => '5'];
        $rules = ['page' => ['required', 'integer']];

        $translator = new Translator(new ArrayLoader, 'en');
        $validatorFactory = new Factory($translator);

        $validator = $validatorFactory->make($data, $rules);

        $this->assertTrue($validator->passes());
        $this->assertSame('5', $validator->validated()['page']);
        $this->assertIsString($validator->validated()['page']);
    }

    public function test_it_should_cast_to_int_with_helper_function()
    {
        $value = '5';

        $intValue = toIntOrNull($value);

        $this->assertSame(5, $intValue);
        $this->assertIsInt($intValue);
    }

    public function test_it_should_return_null_if_value_is_null()
    {
        $value = null;

        $intValue = toIntOrNull($value);

        $this->assertNull($intValue);
    }
}
