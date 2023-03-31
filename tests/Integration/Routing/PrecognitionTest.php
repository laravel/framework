<?php

namespace Illuminate\Tests\Integration\Routing;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Orchestra\Testbench\TestCase;

function fail()
{
    throw new Exception('This code should not be reached.');
}

class PrecognitionTest extends TestCase
{
    public function testItDoesntInvokeControllerMethodByDefault()
    {
        Route::get('test-route', [PrecognitionTestController::class, 'methodThatFails'])
            ->middleware(HandlePrecognitiveRequests::class);

        $response = $this->get('test-route', ['Precognition' => 'true']);

        $response->assertNoContent();
        $this->assertTrue($this->app['ClassWasInstantiated']);
    }

    public function testItDoesntInvokeCallableControllerByDefault()
    {
        $resolved = false;
        Route::get('test-route', fn (ClassThatBindsOnInstantiation $foo) => fail())
            ->middleware(HandlePrecognitiveRequests::class);

        $response = $this->get('test-route', ['Precognition' => 'true']);

        $response->assertNoContent();
        $this->assertTrue($this->app['ClassWasInstantiated']);
    }

    public function testItCanCheckPrecognitiveStateOnTheRequest()
    {
        Route::get('test-route', fn () => fail())
            ->middleware(PrecognitionInvokingController::class);

        $this->get('test-route');
        $this->assertNull(request()->attributes->get('precognitive'));
        $this->assertFalse(request()->isPrecognitive());

        $this->get('test-route', ['Precognition' => 'true']);
        $this->assertTrue(request()->attributes->get('precognitive'));
        $this->assertTrue(request()->isPrecognitive());
    }

    public function testItReturnsTheEmptyResponseWhenNotBailing()
    {
        Route::get('test-route', function () {
            precognitive(function () {
                //
            });

            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->get('test-route', ['Precognition' => 'true']);

        $response->assertNoContent();
        $response->assertHeader('Precognition', 'true');
        $response->assertHeader('Vary', 'Precognition');
    }

    public function testItCanBailDuringPrecognitionRequest()
    {
        Route::get('test-route', function () {
            precognitive(function ($bail) {
                $bail(response()->json(['expected' => 'response']));
                fail();
            });
            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->get('test-route', ['Precognition' => 'true']);

        $response->assertOk();
        $response->assertJson(['expected' => 'response']);
        $response->assertHeader('Precognition', 'true');
    }

    public function testItCanExcludeValidationRulesWhenPrecognitiveWithFormRequest()
    {
        Route::post('test-route', fn (PrecognitionTestRequest $request) => fail())
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            'required_integer' => 'foo',
            'required_integer_when_not_precognitive' => 'foo',
        ], [
            'Precognition' => 'true',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors', [
            'required_integer' => [
                'The required integer must be an integer.',
            ],
        ]);
    }

    public function testItRunsExcludedRulesWhenNotPrecognitiveForFormRequest()
    {
        Route::post('test-route', fn (PrecognitionTestRequest $request) => fail())
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            'required_integer' => 'foo',
            'required_integer_when_not_precognitive' => 'foo',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors', [
            'required_integer' => [
                'The required integer must be an integer.',
            ],
            'required_integer_when_not_precognitive' => [
                'The required integer when not precognitive must be an integer.',
            ],
        ]);
    }

    public function testClientCanSpecifyInputToValidate()
    {
        Route::post('test-route', fn (PrecognitionTestRequest $request) => fail())
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            // 'required_integer' => 'foo',
            'optional_integer_1' => 'foo',
            'optional_integer_2' => 'foo',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1,optional_integer_2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors', [
            'optional_integer_1' => [
                'The optional integer 1 must be an integer.',
            ],
            'optional_integer_2' => [
                'The optional integer 2 must be an integer.',
            ],
        ]);
    }

    public function testClientCanSpecifyNoInputsToValidate()
    {
        Route::post('test-route', fn (PrecognitionTestRequest $request) => fail())
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            // 'required_integer' => 'foo',
            'required_integer_when_not_precognitive' => 'foo',
            'optional_integer_1' => 'foo',
            'optional_integer_2' => 'foo',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => '',
        ]);

        $response->assertNoContent();
    }

    public function testItAppliesHeadersWhenExceptionThrownInPrecognition()
    {
        Route::get('test-route', function () {
            precognitive(function () {
                throw new ModelNotFoundException();
            });
            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->get('test-route', ['Precognition' => 'true']);

        $response->assertNotFound();
        $response->assertHeader('Precognition', 'true');
        $response->assertHeader('Vary', 'Precognition');
    }

    public function testItAppliesHeadersWhenFlowControlExceptionIsThrown()
    {
        // Check with Authorize middleware first...
        Gate::define('alwaysDeny', fn () => false);
        Route::get('test-route-before', fn () => fail())
            ->middleware(['can:alwaysDeny', HandlePrecognitiveRequests::class]);

        $response = $this->get('test-route-before', ['Precognition' => 'true']);

        $response->assertForbidden();
        $response->assertHeader('Precognition', 'true');
        $response->assertHeader('Vary', 'Precognition');

        // Check with Authorize middleware last...
        Route::get('test-route-after', fn () => fail())
            ->middleware([HandlePrecognitiveRequests::class, 'can:alwaysDeny']);

        $response = $this->get('test-route-after', ['Precognition' => 'true']);

        $response->assertForbidden();
        $response->assertHeader('Precognition', 'true');
        $response->assertHeader('Vary', 'Precognition');
    }

    public function testItCanReturnValuesFromPrecognitionClosure()
    {
        Route::get('test-route', function () {
            [$first, $second, $third] = precognitive(function () {
                return ['expected', 'values', 'passed'];
            });

            return [
                'first' => $first,
                'second' => $second,
                'third' => $third,
            ];
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->get('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'first' => 'expected',
            'second' => 'values',
            'third' => 'passed',
        ]);
    }

    public function testItCanBailWithResponseDuringNormalRequest()
    {
        Route::get('test-route', function () {
            precognitive(function ($bail) {
                $bail(response()->json(['expected' => 'response']));

                fail();
            });

            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->get('test-route');

        $response->assertOk();
        $response->assertJson(['expected' => 'response']);
        $response->assertHeaderMissing('Precognition');
    }

    public function testArbitraryBailResponseIsParsedToResponse()
    {
        Route::get('test-route', function () {
            precognitive(function ($bail) {
                $bail(['expected' => 'response']);

                fail();
            });
            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->get('test-route');
        $response->assertJson(['expected' => 'response']);
        $response->assertHeaderMissing('Precognition');

        $response = $this->get('test-route', ['Precognition' => 'true']);
        $response->assertJson(['expected' => 'response']);
        $response->assertHeader('Precognition', 'true');
        $response->assertHeader('Vary', 'Precognition');
    }

    public function testClientCanSpecifyInputsToValidateWhenUsingControllerValidate()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredicitionValidatesViaControllerValidate'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            // 'required_integer' => 'foo',
            'required_integer_when_not_precognitive' => 'foo',
            'optional_integer_1' => 'integer',
            'optional_integer_2' => 'integer',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1,optional_integer_2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors', [
            'optional_integer_1' => [
                'The optional integer 1 must be an integer.',
            ],
            'optional_integer_2' => [
                'The optional integer 2 must be an integer.',
            ],
        ]);
    }

    public function testClientCanSpecifyInputsToValidateWhenUsingControllerValidateWithBag()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredicitionValidatesViaControllerValidateWithBag'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            // 'required_integer' => 'foo',
            'required_integer_when_not_precognitive' => 'foo',
            'optional_integer_1' => 'integer',
            'optional_integer_2' => 'integer',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1,optional_integer_2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors', [
            'optional_integer_1' => [
                'The optional integer 1 must be an integer.',
            ],
            'optional_integer_2' => [
                'The optional integer 2 must be an integer.',
            ],
        ]);
    }

    public function testClientCanSpecifyInputsToValidateWhenUsingRequestValidate()
    {
        Route::post('test-route', function (Request $request) {
            precognitive(function () use ($request) {
                $request->validate([
                    'required_integer' => 'required|integer',
                    ...! $request->isPrecognitive() ? ['required_integer_when_not_precognitive' => 'required|integer'] : [],
                    'optional_integer_1' => 'integer',
                    'optional_integer_2' => 'integer',
                ]);

                fail();
            });
            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            // 'required_integer' => 'foo',
            'required_integer_when_not_precognitive' => 'foo',
            'optional_integer_1' => 'integer',
            'optional_integer_2' => 'integer',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1,optional_integer_2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors', [
            'optional_integer_1' => [
                'The optional integer 1 must be an integer.',
            ],
            'optional_integer_2' => [
                'The optional integer 2 must be an integer.',
            ],
        ]);
    }

    public function testClientCanSpecifyInputsToValidateWhenUsingRequestValidateWithBag()
    {
        Route::post('test-route', function (Request $request) {
            precognitive(function () use ($request) {
                $request->validateWithBag('custom-bag', [
                    'required_integer' => 'required|integer',
                    ! $request->isPrecognitive() ? ['required_integer_when_not_precognitive' => 'required|integer'] : [],
                    'optional_integer_1' => 'integer',
                    'optional_integer_2' => 'integer',
                ]);

                fail();
            });

            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            // 'required_integer' => 'foo',
            'required_integer_when_not_precognitive' => 'foo',
            'optional_integer_1' => 'integer',
            'optional_integer_2' => 'integer',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1,optional_integer_2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors', [
            'optional_integer_1' => [
                'The optional integer 1 must be an integer.',
            ],
            'optional_integer_2' => [
                'The optional integer 2 must be an integer.',
            ],
        ]);
    }

    public function testClientCanSpecifyInputsToValidateWhenUsingControllerValidateWithPassingArrayOfRules()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredicitionValidatesViaControllerValidateWith'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            // 'required_integer' => 'foo',
            'required_integer_when_not_precognitive' => 'foo',
            'optional_integer_1' => 'integer',
            'optional_integer_2' => 'integer',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1,optional_integer_2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors', [
            'optional_integer_1' => [
                'The optional integer 1 must be an integer.',
            ],
            'optional_integer_2' => [
                'The optional integer 2 must be an integer.',
            ],
        ]);
    }

    public function testItAppendsAnAdditionalVaryHeaderInsteadOfReplacingAnyExistingVaryHeaders()
    {
        Route::get('test-route', function () {
            precognitive(function ($bail) {
                $bail(response('expected')->header('Vary', 'Foo'));
                fail();
            });
            fail();
        })->middleware([PrecognitionInvokingController::class]);

        $response = $this->get('test-route', ['Precognition' => 'true']);

        $response->assertHeader('Vary', 'Foo, Precognition');
    }

    public function testSpacesAreImportantInValidationFilterLogicForJsonRequests()
    {
        Route::post('test-route', fn (PrecognitionTestRequest $request) => fail())
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            ' input with spaces ' => 'foo',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => ' input with spaces ',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors', [
            ' input with spaces ' => [
                'The input with spaces must be an integer.',
            ],
        ]);
    }

    public function testVaryHeaderIsAppliedToNonPrecognitionResponses()
    {
        Route::get('test-route', fn () => 'ok')
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->get('test-route');

        $response->assertOk();
        $this->assertSame('ok', $response->content());
        $response->assertHeader('Vary', 'Precognition');
    }

    public function testItStopsExecutionAfterSuccessfulValidationWithValidationFilteringAndFormRequest()
    {
        Route::post('test-route', function (PrecognitionTestRequest $request, ClassThatBindsOnInstantiation $foo) {
            fail();
        })->middleware(PrecognitionInvokingController::class);
        $this->app->instance('ClassWasInstantiated', false);

        $response = $this->post('test-route', [
            'optional_integer_1' => 1,
            'optional_integer_2' => 'foo',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1',
        ]);

        $this->assertFalse($this->app['ClassWasInstantiated']);
        $response->assertNoContent();
        $response->assertHeader('Precognition', 'true');
    }

    public function testItStopsExecutionAfterFailedValidationWithNestedValidationFilteringUsingFormRequest()
    {
        Route::post('test-route', function (NestedPrecognitionTestRequest $request) {
            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            'nested' => [
                ['namsse' => 'sdsd'],
            ],
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'nested,nested.0.name',
        ]);

        $response->assertStatus(422);
        $response->assertExactJson([
            'message' => 'The nested.0.name field is required.',
            'errors' => [
                'nested' => [
                    [
                        'name' => [
                            'The nested.0.name field is required.',
                        ],
                    ],
                ],
            ],
        ]);
        $response->assertHeader('Precognition', 'true');
    }

    public function testItStopsExecutionAfterFailedValidationWithNestedValidationFilteringUsingRequestValidate()
    {
        Route::post('test-route', function (Request $request) {
            $request->validate([
                'nested' => ['required', 'array', 'min:1'],
                'nested.*.name' => ['required', 'string'],
            ]);
            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            'nested' => [
                ['namsse' => 'sdsd'],
            ],
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'nested,nested.0.name',
        ]);

        $response->assertStatus(422);
        $response->assertExactJson([
            'message' => 'The nested.0.name field is required.',
            'errors' => [
                'nested' => [
                    [
                        'name' => [
                            'The nested.0.name field is required.',
                        ],
                    ],
                ],
            ],
        ]);
        $response->assertHeader('Precognition', 'true');
    }

    public function testItStopsExecutionAfterFailedValidationWithNestedValidationFilteringUsingControllerValidate()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWhereNestedRulesAreValidatedViaControllerValidate'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            'nested' => [
                ['namsse' => 'sdsd'],
            ],
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'nested,nested.0.name',
        ]);

        $response->assertStatus(422);
        $response->assertExactJson([
            'message' => 'The nested.0.name field is required.',
            'errors' => [
                'nested' => [
                    [
                        'name' => [
                            'The nested.0.name field is required.',
                        ],
                    ],
                ],
            ],
        ]);
        $response->assertHeader('Precognition', 'true');
    }

    public function testItStopsExecutionAfterFailedValidationWithNestedValidationFilteringUsingControllerValidateWith()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWhereNestedRulesAreValidatedViaControllerValidateWith'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            'nested' => [
                ['namsse' => 'sdsd'],
            ],
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'nested,nested.0.name',
        ]);

        $response->assertStatus(422);
        $response->assertExactJson([
            'message' => 'The nested.0.name field is required.',
            'errors' => [
                'nested' => [
                    [
                        'name' => [
                            'The nested.0.name field is required.',
                        ],
                    ],
                ],
            ],
        ]);
        $response->assertHeader('Precognition', 'true');
    }

    public function testItCanPassValidationForEscapedDotsAfterFilteringWithPrecognition()
    {
        Route::post('test-route', function (PrecognitionRequestWithEscapedDots $request) {
            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            'escaped.dot' => 'value',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'escaped\.dot',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Precognition', 'true');
    }

    public function testItCanFilterRulesWithEscapedDotsUsingFormRequest()
    {
        Route::post('test-route', function (PrecognitionRequestWithEscapedDots $request) {
            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            //
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'escaped\.dot',
        ]);

        $response->assertStatus(422);
        $response->assertExactJson([
            'message' => 'The escaped.dot field is required.',
            'errors' => [
                'escaped.dot' => [
                    'The escaped.dot field is required.',
                ],
            ],
        ]);
        $response->assertHeader('Precognition', 'true');
    }

    public function testItCanFilterRulesWithEscapedDotsWhenUsingRequestValidate()
    {
        Route::post('test-route', function (Request $request) {
            $request->validate([
                'escaped\.dot' => 'required',
            ]);

            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            //
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'escaped\.dot',
        ]);

        $response->assertStatus(422);
        $response->assertExactJson([
            'message' => 'The escaped.dot field is required.',
            'errors' => [
                'escaped.dot' => [
                    'The escaped.dot field is required.',
                ],
            ],
        ]);
        $response->assertHeader('Precognition', 'true');
    }

    public function testItCanFilterRulesWithEscapedDotsWhenUsingControllerValidate()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWhereEscapedDotRuleIsValidatedViaControllerValidate'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            //
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'escaped\.dot',
        ]);

        $response->assertStatus(422);
        $response->assertExactJson([
            'message' => 'The escaped.dot field is required.',
            'errors' => [
                'escaped.dot' => [
                    'The escaped.dot field is required.',
                ],
            ],
        ]);
        $response->assertHeader('Precognition', 'true');
    }

    public function testItCanFilterRulesWithEscapedDotsWhenUsingControllerValidateWith()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWhereEscapedDotRuleIsValidatedViaControllerValidateWith'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->postJson('test-route', [
            //
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'escaped\.dot',
        ]);

        $response->assertStatus(422);
        $response->assertExactJson([
            'message' => 'The escaped.dot field is required.',
            'errors' => [
                'escaped.dot' => [
                    'The escaped.dot field is required.',
                ],
            ],
        ]);
        $response->assertHeader('Precognition', 'true');
    }

    public function testItContinuesExecutionAfterSuccessfulValidationWithoutValidationFilteringAndFormRequest()
    {
        Route::post('test-route', function (PrecognitionTestRequest $request, ClassThatBindsOnInstantiation $foo) {
            precognitive(function ($bail) {
                $bail(response('expected response'));
                fail();
            });
            fail();
        })->middleware(PrecognitionInvokingController::class);
        $this->app->instance('ClassWasInstantiated', false);

        $response = $this->post('test-route', [
            'required_integer' => 1,
        ], [
            'Precognition' => 'true',
        ]);

        $this->assertTrue($this->app['ClassWasInstantiated']);
        $response->assertOk();
        $this->assertSame('expected response', $response->content());
        $response->assertHeader('Precognition', 'true');
    }

    public function testItStopsExecutionAfterSuccessfulValidationWithValidationFilteringAndControllerValidate()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredictionReturnsResponseWithControllerValidate'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->post('test-route', [
            'optional_integer_1' => 1,
            'optional_integer_2' => 'foo',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Precognition', 'true');
    }

    public function testItContinuesExecutionAfterSuccessfulValidationWithoutValidationFilteringAndControllerValidate()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredictionReturnsResponseWithControllerValidate'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->post('test-route', [
            'required_integer' => 1,
        ], [
            'Precognition' => 'true',
        ]);

        $response->assertOk();
        $this->assertSame('Post-validation code was executed.', $response->content());
        $response->assertHeader('Precognition', 'true');
    }

    public function testItStopsExecutionAfterSuccessfulValidationWithValidationFilteringAndControllerValidateWithBag()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredictionReturnsResponseWithControllerValidateWithBag'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->post('test-route', [
            'optional_integer_1' => 1,
            'optional_integer_2' => 'foo',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Precognition', 'true');
    }

    public function testItContinuesExecutionAfterSuccessfulValidationWithoutValidationFilteringAndControllerValidateWithBag()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredictionReturnsResponseWithControllerValidateWithBag'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->post('test-route', [
            'required_integer' => 1,
        ], [
            'Precognition' => 'true',
        ]);

        $response->assertOk();
        $this->assertSame('Post-validation code was executed.', $response->content());
        $response->assertHeader('Precognition', 'true');
    }

    public function testItStopsExecutionAfterSuccessfulValidationWithValidationFilteringAndControllerValidateWith()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredictionReturnsResponseWithControllerValidateWith'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->post('test-route', [
            'optional_integer_1' => 1,
            'optional_integer_2' => 'foo',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Precognition', 'true');
    }

    public function testItContinuesExecutionAfterSuccessfulValidationWithoutValidationFilteringAndControllerValidateWithXXXX()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredictionReturnsResponseWithControllerValidateWith'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->post('test-route', [
            'required_integer' => 1,
        ], [
            'Precognition' => 'true',
        ]);

        $response->assertOk();
        $this->assertSame('Post-validation code was executed.', $response->content());
        $response->assertHeader('Precognition', 'true');
    }

    public function testItStopsExecutionAfterSuccessfulValidationWithValidationFilteringAndControllerValidateWithPassingValidator()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredictionReturnsResponseWithControllerValidateWithPassingValidator'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->post('test-route', [
            'optional_integer_1' => 1,
            'optional_integer_2' => 'foo',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Precognition', 'true');
    }

    public function testItContinuesExecutionAfterSuccessfulValidationWithoutValidationFilteringAndControllerValidateWithPassingValidator()
    {
        Route::post('test-route', [PrecognitionTestController::class, 'methodWherePredictionReturnsResponseWithControllerValidateWithPassingValidator'])
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->post('test-route', [
            'required_integer' => 1,
        ], [
            'Precognition' => 'true',
        ]);

        $response->assertOk();
        $this->assertSame('Post-validation code was executed.', $response->content());
        $response->assertHeader('Precognition', 'true');
    }

    public function testItStopsExecutionAfterSuccessfulValidationWithValidationFilteringAndRequestValidate()
    {
        Route::post('test-route', function (Request $request) {
            precognitive(function ($bail) use ($request) {
                $request->validate([
                    'required_integer' => 'required|integer',
                    'optional_integer_1' => 'integer',
                    'optional_integer_2' => 'integer',
                ]);

                $bail(response('Post-validation code was executed.'));

                fail();
            });

            fail();
        })
            ->middleware(PrecognitionInvokingController::class);

        $response = $this->post('test-route', [
            'optional_integer_1' => 1,
            'optional_integer_2' => 'foo',
        ], [
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'optional_integer_1',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Precognition', 'true');
    }

    public function testItContinuesExecutionAfterSuccessfulValidationWithoutValidationFilteringAndRequestValidate()
    {
        Route::post('test-route', function (Request $request) {
            precognitive(function ($bail) use ($request) {
                $request->validate([
                    'required_integer' => 'required|integer',
                    'optional_integer_1' => 'integer',
                    'optional_integer_2' => 'integer',
                ]);

                $bail(response('Post-validation code was executed.'));

                fail();
            });

            fail();
        })->middleware(PrecognitionInvokingController::class);

        $response = $this->post('test-route', [
            'required_integer' => 1,
        ], [
            'Precognition' => 'true',
        ]);

        $response->assertOk();
        $this->assertSame('Post-validation code was executed.', $response->content());
        $response->assertHeader('Precognition', 'true');
    }

    public function testItDoesNotSetLastUrl()
    {
        Route::get('expected-route-1', fn () => 'ok')->middleware(StartSession::class);
        Route::get('expected-route-2', fn () => 'ok')->middleware(StartSession::class);
        Route::get('precognition-route', fn () => 'ok')->middleware([StartSession::class, HandlePrecognitiveRequests::class]);

        $this->app->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        $response = $this->get('expected-route-1');
        $response->assertOk();
        $this->assertSame('http://localhost/expected-route-1', session()->previousUrl());

        $this->app->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        $response = $this->get('precognition-route', ['Precognition' => 'true']);
        $response->assertNoContent();
        $this->assertSame('http://localhost/expected-route-1', session()->previousUrl());

        $this->app->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        $response = $this->get('expected-route-2');
        $response->assertOk();
        $this->assertSame('http://localhost/expected-route-2', session()->previousUrl());
    }

    public function testItAppendsVaryHeaderToSymfonyResponse()
    {
        Route::get('test-route', function () {
            return response()->streamDownload(function () {
                echo 'foo';
            }, null, ['Expected' => 'Header']);
        })->middleware(HandlePrecognitiveRequests::class);

        $response = $this->get('test-route');
        $response->assertOk();
        $response->assertHeader('Expected', 'Header');
    }

    public function testItAppendsPrecognitionHeaderToSymfonyResponse()
    {
        Route::get('test-route', function () {
            //
        })->middleware([
            HandlePrecognitiveRequests::class,
            MiddlewareReturningSymfonyResponse::class,
        ]);

        $response = $this->get('test-route', ['Precognition' => 'true']);
        $response->assertOk();
        $response->assertHeader('Expected', 'Header');
        $response->assertHeader('Precognition', 'true');
    }
}

class PrecognitionTestController
{
    use ValidatesRequests;

    public function methodWhereEscapedDotRuleIsValidatedViaControllerValidate(Request $request)
    {
        precognitive(function () use ($request) {
            $this->validate($request, [
                'escaped\.dot' => 'required',
            ]);

            fail();
        });

        fail();
    }

    public function methodWhereEscapedDotRuleIsValidatedViaControllerValidateWith()
    {
        precognitive(function () {
            $this->validateWith([
                'escaped\.dot' => 'required',
            ]);

            fail();
        });

        fail();
    }

    public function methodWhereNestedRulesAreValidatedViaControllerValidate(Request $request)
    {
        precognitive(function () use ($request) {
            $this->validate($request, [
                'nested' => ['required', 'array', 'min:1'],
                'nested.*.name' => ['required', 'string'],
            ]);

            fail();
        });

        fail();
    }

    public function methodWhereNestedRulesAreValidatedViaControllerValidateWith(Request $request)
    {
        precognitive(function () {
            $this->validateWith([
                'nested' => ['required', 'array', 'min:1'],
                'nested.*.name' => ['required', 'string'],
            ]);

            fail();
        });

        fail();
    }

    public function methodWherePredicitionValidatesViaControllerValidate(Request $request)
    {
        precognitive(function () use ($request) {
            $this->validate($request, [
                'required_integer' => 'required|integer',
                ...! $request->isPrecognitive() ? ['required_integer_when_not_precognitive' => 'required|integer'] : [],
                'optional_integer_1' => 'integer',
                'optional_integer_2' => 'integer',
            ]);

            fail();
        });

        fail();
    }

    public function methodWherePredicitionValidatesViaControllerValidateWithBag(Request $request)
    {
        precognitive(function () use ($request) {
            $this->validateWithBag('custom-bag', $request, [
                'required_integer' => 'required|integer',
                ...! $request->isPrecognitive() ? ['required_integer_when_not_precognitive' => 'required|integer'] : [],
                'optional_integer_1' => 'integer',
                'optional_integer_2' => 'integer',
            ]);

            fail();
        });

        fail();
    }

    public function methodWherePredicitionValidatesViaControllerValidateWith(Request $request)
    {
        precognitive(function () use ($request) {
            $this->validateWith([
                'required_integer' => 'required|integer',
                ...! $request->isPrecognitive() ? ['required_integer_when_not_precognitive' => 'required|integer'] : [],
                'optional_integer_1' => 'integer',
                'optional_integer_2' => 'integer',
            ]);

            fail();
        });

        fail();
    }

    public function methodWherePredictionReturnsResponseWithControllerValidate(Request $request)
    {
        precognitive(function ($bail) use ($request) {
            $this->validate($request, [
                'required_integer' => 'required|integer',
                'optional_integer_1' => 'integer',
                'optional_integer_2' => 'integer',
            ]);

            $bail(response('Post-validation code was executed.'));

            fail();
        });

        fail();
    }

    public function methodWherePredictionReturnsResponseWithControllerValidateWithBag(Request $request)
    {
        precognitive(function ($bail) use ($request) {
            $this->validateWithBag('custom-bag', $request, [
                'required_integer' => 'required|integer',
                'optional_integer_1' => 'integer',
                'optional_integer_2' => 'integer',
            ]);

            $bail(response('Post-validation code was executed.'));

            fail();
        });

        fail();
    }

    public function methodWherePredictionReturnsResponseWithControllerValidateWith(Request $request)
    {
        precognitive(function ($bail) {
            $this->validateWith([
                'required_integer' => 'required|integer',
                'optional_integer_1' => 'integer',
                'optional_integer_2' => 'integer',
            ]);

            $bail(response('Post-validation code was executed.'));

            fail();
        });

        fail();
    }

    public function methodWherePredictionReturnsResponseWithControllerValidateWithPassingValidator(Request $request)
    {
        precognitive(function ($bail) use ($request) {
            $this->validateWith(Validator::make($request->all(), [
                'required_integer' => 'required|integer',
                'optional_integer_1' => 'integer',
                'optional_integer_2' => 'integer',
            ]));

            $bail(response('Post-validation code was executed.'));

            fail();
        });

        fail();
    }

    public function methodThatFails(ClassThatBindsOnInstantiation $foo)
    {
        fail();
    }
}

class PrecognitionTestRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'required_integer' => 'required|integer',
            'optional_integer_1' => 'integer',
            'optional_integer_2' => 'integer',
            ' input with spaces ' => 'integer',
        ];

        if (! $this->isPrecognitive()) {
            $rules['required_integer_when_not_precognitive'] = 'required|integer';
        }

        return $rules;
    }
}

class NestedPrecognitionTestRequest extends FormRequest
{
    public function rules()
    {
        return [
            'nested' => ['required', 'array', 'min:1'],
            'nested.*.name' => ['required', 'string'],
        ];
    }
}

class PrecognitionRequestWithEscapedDots extends FormRequest
{
    public function rules()
    {
        return [
            'escaped\.dot' => ['required'],
        ];
    }
}

class ClassThatBindsOnInstantiation
{
    public function __construct()
    {
        app()->instance('ClassWasInstantiated', true);
    }
}

class PrecognitionInvokingController extends HandlePrecognitiveRequests
{
    protected function prepareForPrecognition($request)
    {
        parent::prepareForPrecognition($request);

        app()->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));
        app()->bind(ControllerDispatcherContract::class, fn ($app) => new ControllerDispatcher($app));
    }
}

class MiddlewareReturningSymfonyResponse
{
    public function handle($request, $next)
    {
        return response()->streamDownload(function () {
            //
        }, null, ['Expected' => 'Header']);
    }
}
