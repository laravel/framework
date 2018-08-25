<?php

namespace Illuminate\Tests\Validation;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Validator;
use Illuminate\Contracts\Translation\Translator;

class ValidatesAttributesTest extends TestCase
{
    /**
     * @var Translator
     */
    protected $translator;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = Mockery::spy(Translator::class);
    }

    /**
     * @dataProvider passingValidatorProvider
     */
    public function test_passing_validator($value, $rules)
    {
        $validator = new Validator($this->translator, ['value' => $value], ['value' => $rules]);

        self::assertTrue($validator->passes());
    }

    public function passingValidatorProvider()
    {
        return [
            'pass bool rule with true'      => [true, 'bool'],
            'pass bool rule with false'     => [false, 'bool'],
            'pass bool rule with 1'         => [1, 'bool'],
            'pass bool rule with 0'         => [0, 'bool'],
            'pass bool rule with string 1'  => ['1', 'bool'],
            'pass bool rule with string 0'  => ['0', 'bool'],
            'pass bool rule with empty'     => ['', 'bool'],

            'pass accepted rule with true'        => [true, 'accepted'],
            'pass accepted rule with string true' => ['true', 'accepted'],
            'pass accepted rule with on'          => ['on', 'accepted'],
            'pass accepted rule with 1'           => [1, 'accepted'],
            'pass accepted rule with yes'         => ['yes', 'accepted'],

            'pass alpha rule with lowercase' => ['abcdefghijklmnopqrstuvwxyz', 'alpha'],
            'pass alpha rule with uppercase' => ['ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'alpha'],

            'pass max rule' => [5, 'max:10'],
            'pass max rule at the exact limit' => [200, 'max:200'],

            'pass array rule with array'       => [[1, 2, 3], 'array'],
            'pass array rule with empty array' => [[], 'array'],
        ];
    }

    /**
     * @dataProvider failingValidatorProvider
     */
    public function test_failing_validator($value, $rules)
    {
        $validator = new Validator($this->translator, ['value' => $value], ['value' => $rules]);

        self::assertTrue($validator->fails());
    }

    public function failingValidatorProvider()
    {
        return [
            'fail bool rule with string' => ['a', 'bool'],

            'fail accept rule with string' => ['a', 'accepted'],
            'fail accept rule with off'    => ['off', 'accepted'],
            'fail accept rule with 0'      => [0, 'accepted'],

            'fail required rule with empty'       => ['', 'required'],
            'fail required rule with null'        => [null, 'required'],
            'fail required rule with empty array' => [[], 'required'],
        ];
    }

    public function test_validate_after_and_before_date()
    {
        $start = '2018-01-01';
        $end = '2018-12-31';

        $validator = new Validator($this->translator, [
            'start' => $start,
            'end' => $end,
        ], [
            'start' => 'required|date|before:end',
            'end' => 'required|date|after:start',
        ]);

        self::assertTrue($validator->passes());
    }

    public function test_validate_after_date()
    {
        $value = '2018-12-31 23:59:59';

        $validator = new Validator($this->translator, [
            'start' => $value,
        ], [
            'start' => 'date|date_format:Y-m-d H:i:s',
        ]);

        self::assertTrue($validator->passes());
    }
}
