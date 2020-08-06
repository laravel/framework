<?php

namespace Illuminate\Tests\Validation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Factory;
use Illuminate\Container\Container;
use Illuminate\Tests\Validation\fixtures\CustomRule;
use Illuminate\Tests\Validation\fixtures\RuleDependency;
use Illuminate\Tests\Validation\fixtures\CustomRuleWithDependency;
use Illuminate\Contracts\Translation\Translator as TranslatorInterface;

class ValidationCustomRuleTest extends TestCase
{
    public function testUsingCustumRule()
    {
        $translator = m::mock(TranslatorInterface::class);
        $factory = new Factory($translator);

        $validator = $factory->make(['foo' => 'bar'], ['custom' => new CustomRule]);

        $this->assertTrue($validator->passes());
    }

    public function testUsingCustumRuleWithDependency()
    {
        $container = tap(new Container, function ($container) {
            $container->instance(
                CustomRuleWithDependency::class,
                new CustomRuleWithDependency(new RuleDependency())
            );
        });
        $translator = m::mock(TranslatorInterface::class);
        $factory = new Factory($translator, $container);

        $validator = $factory->make(['foo' => 'bar'], ['custom' => CustomRuleWithDependency::class]);

        $this->assertTrue($validator->passes());
    }
}
