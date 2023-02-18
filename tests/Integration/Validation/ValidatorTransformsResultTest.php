<?php
declare(strict_types=1);

namespace Illuminate\Tests\Integration\Validation;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\TransformsResultRule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Validator;
use Orchestra\Testbench\TestCase;

class ValidatorTransformsResultTest extends TestCase
{
    public function testRuleTransformsResult()
    {
        $validator = $this->getValidator(['value' => '1'], ['value' => new ValidFloat()]);
        $data = $validator->validate();
        $this->assertIsFloat($data['value']);
        $this->assertSame(1.0, $data['value']);
    }

    public function testRuleWithoutTransformIsNotCalledTransformsResult()
    {
        $rule = \Mockery::mock(Rule::class);
        $rule->shouldReceive('passes')->andReturn(true);
        $validator = $this->getValidator(['value' => '1'], ['value' => $rule]);
        $data = $validator->validate();
        $this->assertIsString($data['value']);
    }

    public function testTransformIsNotCalledOnFailure()
    {
        $rule = \Mockery::mock(Rule::class, TransformsResultRule::class);
        $rule->shouldReceive('passes')->andReturn(false);
        $rule->shouldReceive('message')->andReturn('tset');
        $rule->shouldNotReceive('transform');
        $validator = $this->getValidator(['value' => '1'], ['value' => $rule]);
        $this->assertTrue($validator->fails());
    }

    public function testSecondRuleGetsTransformedValueFromFirstRule()
    {
        $rule = \Mockery::mock(Rule::class);
        $rule->shouldReceive('passes')->with('value', 1.0)->andReturn(true);
        $rule->shouldNotReceive('transform');
        $validator = $this->getValidator(['value' => '1'], ['value' => [new ValidFloat(), $rule]]);
        $this->assertTrue($validator->passes());
    }

    protected function getValidator(array $data, array $rules): Validator
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, $data, $rules);
        $validator->setPresenceVerifier(new DatabasePresenceVerifier($this->app['db']));

        return $validator;
    }
}


class ValidFloatBase implements Rule {
    public function passes($attribute, $value)
    {
        return true;
    }

    public function message()
    {
        return 'float';
    }
}


class ValidFloat extends ValidFloatBase implements TransformsResultRule {
    public function transform(string $attribute, mixed $value, array $context): mixed
    {
        return ((float) $value);
    }
}
