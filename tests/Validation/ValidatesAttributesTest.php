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

    public function test_validate_bool()
    {
        $validator = new Validator($this->translator, [
            'attr_1' => 1,
            'attr_2' => true,
        ], [
            'attr_1' => 'required|bool',
            'attr_2' => 'required|bool',
        ]);

        self::assertTrue($validator->passes());
    }

    public function test_validate_accepted()
    {
        $validator = new Validator($this->translator, [
            'attr_1' => 1,
            'attr_2' => true,
            'attr_3' => 'true',
            'attr_4' => 'yes',
            'attr_5' => 'on',
        ], [
            'attr_1' => 'required|accepted',
            'attr_2' => 'required|accepted',
            'attr_3' => 'required|accepted',
            'attr_4' => 'required|accepted',
            'attr_5' => 'required|accepted',
        ]);

        self::assertTrue($validator->passes());
    }

    public function test_validate_alpha()
    {
        $validator = new Validator($this->translator, [
            'attr_1' => 'abcdefghijklmnopqrstuvwxyz',
        ], [
            'attr_1' => 'required|alpha',
       ]);

        self::assertTrue($validator->passes());
    }

    public function test_validate_max()
    {
        $validator = new Validator($this->translator, [
            'attr' => 5,
        ], [
            'attr' => 'int|max:4',
        ]);

        self::assertTrue($validator->fails());
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
