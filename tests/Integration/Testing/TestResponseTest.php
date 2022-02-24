<?php

namespace Illuminate\Tests\Integration\Testing;

use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\ExpectationFailedException;

class TestResponseTest extends TestCase
{
    public function testassertJsonValidationErrorRuleWithString()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['key' => 'The key field is required.'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrorRule('key', 'required');
    }

    public function testassertJsonValidationErrorRuleWithArray()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['key' => 'The key field is required.'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrorRule(['key' => 'required']);
    }

    public function testassertJsonValidationErrorRuleWithCustomRule()
    {
        $rule = new class implements RuleContract
        {
            public function passes($attribute, $value)
            {
                return true;
            }

            public function message()
            {
                return ':attribute must be baz';
            }
        };

        $data = [
            'status' => 'ok',
            'errors' => ['key' => 'key must be baz'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrorRule('key', $rule);
    }

    public function testassertJsonValidationErrorRuleWithNoRule()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('No validation rule was provided.');

        $response = TestResponse::fromBaseResponse(new Response());
        $response->assertJsonValidationErrorRule('foo');
    }
}
