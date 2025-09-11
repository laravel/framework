<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationRequiredIfTest extends TestCase
{
    public function testItClosureReturnsFormatsAStringVersionOfTheRule()
    {
        $rule = new RequiredIf(function () {
            return true;
        });

        $this->assertSame('required', (string) $rule);

        $rule = new RequiredIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new RequiredIf(true);

        $this->assertSame('required', (string) $rule);

        $rule = new RequiredIf(false);

        $this->assertSame('', (string) $rule);
    }

    public function testItOnlyCallableAndBooleanAreAcceptableArgumentsOfTheRule()
    {
        $rule = new RequiredIf(false);

        $rule = new RequiredIf(true);

        $this->expectException(InvalidArgumentException::class);

        $rule = new RequiredIf('phpinfo');
    }

    public function testItReturnedRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        $rule = serialize(new RequiredIf(function () {
            return true;
        }));
    }

    public function testRequiredIfRuleValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $rule = new RequiredIf(true);

        $v = new Validator($trans, ['x' => 'foo'], ['x' => $rule]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => ''], ['x' => (string) $rule]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => 'foo'], ['x' => [$rule]]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'foo'], ['x' => ['string', $rule]]);
        $this->assertTrue($v->passes());

        $rule = new RequiredIf(false);

        $v = new Validator($trans, ['x' => 'foo'], ['x' => ['string', $rule]]);
        $this->assertTrue($v->passes());

        $rule = new RequiredIf(null);

        $v = new Validator($trans, ['x' => 'foo'], ['x' => ['string', $rule]]);
        $this->assertTrue($v->passes());
    }
}
