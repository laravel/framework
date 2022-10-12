<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function testExceptionSummarizesZeroErrors()
    {
        $exception = $this->getException([], []);

        $this->assertSame('The given data was invalid.', $exception->getMessage());
    }

    public function testExceptionSummarizesOneError()
    {
        $exception = $this->getException([], ['foo' => 'required']);

        $this->assertSame('validation.required', $exception->getMessage());
    }

    public function testExceptionSummarizesTwoErrors()
    {
        $exception = $this->getException([], ['foo' => 'required', 'bar' => 'required']);

        $this->assertSame('validation.required (and 1 more error)', $exception->getMessage());
    }

    public function testExceptionSummarizesThreeOrMoreErrors()
    {
        $exception = $this->getException([], [
            'foo' => 'required',
            'bar' => 'required',
            'baz' => 'required',
        ]);

        $this->assertSame('validation.required (and 2 more errors)', $exception->getMessage());
    }

    protected function getException($data = [], $rules = [])
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, $data, $rules);

        return new ValidationException($validator);
    }
}
