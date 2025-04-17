<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorAfterRuleTest extends TestCase
{
    public function testAfterAcceptsArrayOfRules()
    {
        $validator = new Validator(new Translator(new ArrayLoader, 'en'), [], []);

        $validator->after([
            fn ($validator) => $validator->errors()->add('closure', 'true'),
            new InvokableAfterRule,
            new AfterMethodRule,
        ])->messages()->messages();

        $this->assertSame($validator->messages()->messages(), [
            'closure' => ['true'],
            'invokableAfterRule' => ['true'],
            'afterMethodRule' => ['true'],
        ]);
    }

    public function testAfterAcceptsSingleClosure()
    {
        $this->validator->after(
            fn ($validator) => $validator->errors()->add('single_closure', 'error')
        );

        $this->assertArrayHasKey('single_closure', $this->validator->messages()->messages());
        $this->assertContains('error', $this->validator->messages()->messages()['single_closure']);
    }

    public function testAfterWithEmptyArray()
    {
        $this->validator->after([]);
        $this->assertEmpty($this->validator->messages()->messages());
    }

    public function testAfterWithMultipleErrorsFromSameRule()
    {
        $this->validator->after(function ($validator) {
            $validator->errors()->add('field1', 'error1');
            $validator->errors()->add('field1', 'error2');
            $validator->errors()->add('field2', 'error3');
        });

        $messages = $this->validator->messages()->messages();
        $this->assertCount(2, array_keys($messages));
        $this->assertCount(2, $messages['field1']);
        $this->assertCount(1, $messages['field2']);
    }

    public function testChainedAfterCalls()
    {
        $this->validator
            ->after(fn ($validator) => $validator->errors()->add('first', 'first_error'))
            ->after(fn ($validator) => $validator->errors()->add('second', 'second_error'));

        $messages = $this->validator->messages()->messages();
        $this->assertArrayHasKey('first', $messages);
        $this->assertArrayHasKey('second', $messages);
    }
}

class InvokableAfterRule
{
    public function __invoke($validator)
    {
        $validator->errors()->add('invokableAfterRule', 'true');
    }
}

class AfterMethodRule
{
    public function __invoke()
    {
        //
    }

    public function after($validator)
    {
        $validator->errors()->add('afterMethodRule', 'true');
    }
}
