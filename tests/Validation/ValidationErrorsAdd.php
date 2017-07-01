<?php

namespace Illuminate\Tests\Validation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Factory;
use Illuminate\Contracts\Translation\Translator;

class ValidationErrorsAdd extends TestCase
{
    public function testFailsCalledAfterErrorsAddDoesNotReturnTrue()
    {
        $translator = m::mock(Translator::class);
        $factory = new Factory($translator);
        $validator = $factory->make(['foo' => 'bar'], ['baz' => 'boom']);

        $this->assertEquals(false, $validator->fails());

        $validator->errors()->add('foo', 'bar');

        $this->assertEquals(true, $validator->fails());
    }
}
