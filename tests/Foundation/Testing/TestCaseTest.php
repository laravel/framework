<?php

namespace Tests\Foundation\Testing;

use Exception;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Session\Store;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCaseTest extends BaseTestCase
{
    public function test_it_includes_response_exceptions_on_test_failures()
    {
        $testCase = new ExampleTestCase('foo');
        $testCase::$latestResponse = TestResponse::fromBaseResponse(new Response())
            ->withExceptions(collect([new Exception('Unexpected exception.')]));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/Assertion message.*Unexpected exception/s');

        $testCase::$latestResponse->transformNotSuccessfulException(
            $exception = new ExpectationFailedException('Assertion message.'),
        );

        throw $exception;
    }

    public function test_it_includes_validation_errors_on_test_failures()
    {
        $testCase = new ExampleTestCase('foo');
        $testCase::$latestResponse = TestResponse::fromBaseResponse(
            tap(new RedirectResponse('/'))
                ->setSession(new Store('test-session', new NullSessionHandler()))
                ->withErrors([
                    'first_name' => 'The first name field is required.',
                ])
        );

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/Assertion message.*The first name field is required/s');

        $testCase::$latestResponse->transformNotSuccessfulException(
            $exception = new ExpectationFailedException('Assertion message.'),
        );

        throw $exception;
    }

    public function test_it_includes_json_validation_errors_on_test_failures()
    {
        $testCase = new ExampleTestCase('foo');
        $testCase::$latestResponse = TestResponse::fromBaseResponse(
            new Response(['errors' => ['first_name' => 'The first name field is required.']])
        );

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/Assertion message.*The first name field is required/s');

        $testCase::$latestResponse->transformNotSuccessfulException(
            $exception = new ExpectationFailedException('Assertion message.'),
        );

        throw $exception;
    }

    public function test_it_doesnt_fail_with_false_json()
    {
        $testCase = new ExampleTestCase('foo');
        $testCase::$latestResponse = TestResponse::fromBaseResponse(
            new Response(false, 200, ['Content-Type' => 'application/json'])
        );

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/Assertion message/s');

        $testCase::$latestResponse->transformNotSuccessfulException(
            $exception = new ExpectationFailedException('Assertion message.'),
        );

        throw $exception;
    }

    public function test_it_doesnt_fail_with_encoded_json()
    {
        $testCase = new ExampleTestCase('foo');
        $testCase::$latestResponse = TestResponse::fromBaseResponse(
            tap(new Response, function ($response) {
                $response->header('Content-Type', 'application/json');
                $response->header('Content-Encoding', 'gzip');
                $response->setContent('b"x£½V*.I,)-V▓R╩¤V¬\x05\x00+ü\x059"');
            })
        );

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/Assertion message/s');

        $testCase::$latestResponse->transformNotSuccessfulException(
            $exception = new ExpectationFailedException('Assertion message.'),
        );

        throw $exception;
    }

    protected function tearDown(): void
    {
        ExampleTestCase::$latestResponse = null;

        parent::tearDown();
    }
}

class ExampleTestCase extends TestCase
{
    public function createApplication()
    {
        //
    }
}
