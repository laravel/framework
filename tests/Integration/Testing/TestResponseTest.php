<?php

namespace Illuminate\Tests\Integration\Testing;

use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\ExpectationFailedException;
use Orchestra\Testbench\TestCase;

class TestResponseTest extends TestCase
{
    public function testAssertJsonValidationErrorRulesWithString()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['key' => 'The key field is required.'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrorRules('key', 'required');
    }

    public function testAssertJsonValidationErrorRulesWithArray()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['key' => 'The key field is required.'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrorRules(['key' => 'required']);
    }

    public function testAssertJsonValidationErrorRulesWithNoRule()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('No validation rule was provided.');

        $response = TestResponse::fromBaseResponse(new Response());
        $response->assertJsonValidationErrorRules('foo');
    }
}
