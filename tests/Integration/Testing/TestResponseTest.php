<?php

namespace Illuminate\Tests\Integration\Testing;

use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\ExpectationFailedException;
use Orchestra\Testbench\TestCase;

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

    public function testassertJsonValidationErrorRuleWithNoRule()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('No validation rule was provided.');

        $response = TestResponse::fromBaseResponse(new Response());
        $response->assertJsonValidationErrorRule('foo');
    }
}
