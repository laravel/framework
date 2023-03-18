<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

function localFunction(Validator $validator) {
    $validator->errors()->add('localFunction', 'true');
}

class ValidatorAfterRuleTest extends TestCase
{
    public function testItAcceptsFunctionString()
    {
        $validator = $this->validator();

        $messages = $validator->after('Illuminate\Tests\Validation\localFunction')->messages()->messages();

        $this->assertSame($validator->messages()->messages(), [
            'localFunction' => ['true'],
        ]);
    }

    public function testItAcceptsInvokableClass()
    {
        $validator = $this->validator();

        $messages = $validator->after(InvokableClass::class)->messages()->messages();

        $this->assertSame($messages, [
            'invokable' => ['true'],
        ]);
    }

    public function testItResolvesDependencies()
    {
        $validator = $this->validator();
        $container = new Container;
        $container->bind(InjectedDependency::class, fn () => new InjectedDependency('expected-value'));
        $validator->setContainer($container);

        $messages = $validator->after(InvokableClassWithDependency::class)->messages()->messages();

        $this->assertSame($messages, [
            'invokableWithDependency' => ['expected-value'],
        ]);
    }

    public function testItSupportsAnArray()
    {
        $validator = $this->validator();
        $container = new Container;
        $container->bind(InjectedDependency::class, fn () => new InjectedDependency('expected-value'));
        $validator->setContainer($container);

        $validator->after([
            fn ($validator) => $validator->errors()->add('callable', 'true'),
            'Illuminate\Tests\Validation\localFunction',
            InvokableClass::class,
            InvokableClassWithDependency::class,
        ])->messages()->messages();

        $this->assertSame($validator->messages()->messages(), [
            'callable' => ['true'],
            'localFunction' => ['true'],
            'invokable' => ['true'],
            'invokableWithDependency' => ['expected-value'],
        ]);
    }

    public function testItCanStopSubsequentValidationRules()
    {
        $validator = $this->validator();
        $container = new Container;
        $container->bind(InjectedDependency::class, fn () => new InjectedDependency('expected-value'));
        $validator->setContainer($container);

        $messages = $validator->after([
            fn ($validator) => $validator->errors()->add('callable', 'true'),
            'Illuminate\Tests\Validation\localFunction',
            InvokableClass::class,
            function ($validator) {
                $validator->skipSubsequentAfterRules();
            },
            InvokableClassWithDependency::class,
        ])->messages()->messages();

        $this->assertSame($messages, [
            'callable' => ['true'],
            'localFunction' => ['true'],
            'invokable' => ['true'],
        ]);
    }

    private function validator(): Validator
    {
        $validator = new Validator(new Translator(new ArrayLoader, 'en'), [], []);

        $validator->setContainer(new Container);

        return $validator;
    }
}

class InvokableClass
{
    public function __invoke(Validator $validator)
    {
        $validator->errors()->add('invokable', 'true');
    }
}

class InvokableClassWithDependency
{
    public function __construct(private InjectedDependency $dependency)
    {
        //
    }

    public function __invoke(Validator $validator)
    {
        $validator->errors()->add('invokableWithDependency', $this->dependency->value);
    }
}

class InjectedDependency
{
    public function __construct(public $value)
    {
        //
    }
}
