<?php

namespace Illuminate\Tests\Validation;

use Exception;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\ExcludeIf;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationExcludeIfTest extends TestCase
{
    public function testItReturnsStringVersionOfRuleWhenCast()
    {
        $rule = new ExcludeIf(function () {
            return true;
        });

        $this->assertSame('exclude', (string) $rule);

        $rule = new ExcludeIf(function () {
            return false;
        });

        $this->assertSame('', (string) $rule);

        $rule = new ExcludeIf(true);

        $this->assertSame('exclude', (string) $rule);

        $rule = new ExcludeIf(false);

        $this->assertSame('', (string) $rule);
    }

    public function testItValidatesCallableAndBooleanAreAcceptableArguments()
    {
        new ExcludeIf(false);
        new ExcludeIf(true);
        new ExcludeIf(fn () => true);

        foreach ([1, 1.1, 'phpinfo', new stdClass, null] as $condition) {
            try {
                new ExcludeIf($condition);
                $this->fail('The ExcludeIf constructor must not accept '.gettype($condition));
            } catch (InvalidArgumentException $exception) {
                $this->assertEquals('The provided condition must be a callable or boolean.', $exception->getMessage());
            }
        }
    }

    public function testItThrowsExceptionIfRuleIsNotSerializable()
    {
        $this->expectException(Exception::class);

        serialize(new ExcludeIf(function () {
            return true;
        }));
    }

    public function testExcludeIfRuleValidation()
    {
        $ruleTrue = new ExcludeIf(true);

        $ruleFalse = new ExcludeIf(false);

        $trans = new Translator(new ArrayLoader, 'en');

        $data = ['foo' => 'FOO', 'bar' => 'BAR'];

        $v = new Validator($trans, $data, ['foo' => $ruleTrue, 'bar' => 'nullable']);
        $this->assertTrue($v->passes());
        $this->assertSame(['bar' => 'BAR'], $v->validated());

        $v = new Validator($trans, $data, ['foo' => (string) $ruleTrue, 'bar' => 'nullable']);
        $this->assertTrue($v->passes());
        $this->assertSame(['bar' => 'BAR'], $v->validated());

        $v = new Validator($trans, $data, ['foo' => [$ruleTrue], 'bar' => 'nullable']);
        $this->assertTrue($v->passes());
        $this->assertSame(['bar' => 'BAR'], $v->validated());

        $v = new Validator($trans, $data, ['foo' => $ruleFalse, 'bar' => 'nullable']);
        $this->assertTrue($v->passes());
        $this->assertSame($data, $v->validated());
    }
}
