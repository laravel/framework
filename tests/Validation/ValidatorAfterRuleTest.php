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
