<?php

namespace Illuminate\Tests\Foundation;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactoryContract;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\Attributes\ErrorBag;
use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator as TranslatorConcrete;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FoundationFormRequestTest extends TestCase
{
    protected $mocks = [];

    protected function tearDown(): void
    {
        FormRequest::failOnUnknownFields(false);

        Container::setInstance(null);

        $this->mocks = [];

        parent::tearDown();
    }

    public function testValidatedMethodReturnsTheValidatedData()
    {
        $request = $this->createRequest(['name' => 'specified', 'with' => 'extras']);

        $request->validateResolved();

        $this->assertEquals(['name' => 'specified'], $request->validated());
    }

    public function testValidatedMethodReturnsTheValidatedDataNestedRules()
    {
        $payload = ['nested' => ['foo' => 'bar', 'baz' => ''], 'array' => [1, 2]];

        $request = $this->createRequest($payload, FoundationTestFormRequestNestedStub::class);

        $request->validateResolved();

        $this->assertEquals(['nested' => ['foo' => 'bar'], 'array' => [1, 2]], $request->validated());
    }

    public function testValidatedMethodReturnsTheValidatedDataNestedChildRules()
    {
        $payload = ['nested' => ['foo' => 'bar', 'with' => 'extras']];

        $request = $this->createRequest($payload, FoundationTestFormRequestNestedChildStub::class);

        $request->validateResolved();

        $this->assertEquals(['nested' => ['foo' => 'bar']], $request->validated());
    }

    public function testValidatedMethodReturnsTheValidatedDataNestedArrayRules()
    {
        $payload = ['nested' => [['bar' => 'baz', 'with' => 'extras'], ['bar' => 'baz2', 'with' => 'extras']]];

        $request = $this->createRequest($payload, FoundationTestFormRequestNestedArrayStub::class);

        $request->validateResolved();

        $this->assertEquals(['nested' => [['bar' => 'baz'], ['bar' => 'baz2']]], $request->validated());
    }

    public function testValidatedMethodNotValidateTwice()
    {
        $payload = ['name' => 'specified', 'with' => 'extras'];

        $request = $this->createRequest($payload, FoundationTestFormRequestTwiceStub::class);

        $request->validateResolved();
        $request->validated();

        $this->assertEquals(1, FoundationTestFormRequestTwiceStub::$count);
    }

    public function testValidateThrowsWhenValidationFails()
    {
        $this->expectException(ValidationException::class);

        $request = $this->createRequest(['no' => 'name']);

        $this->mocks['redirect']->shouldReceive('withInput->withErrors');

        $request->validateResolved();
    }

    public function testValidateThrowsWhenValidationFailsWithConfiguredErrorBagAttribute()
    {
        $request = $this->createRequest(['no' => 'name'], FoundationTestFormRequestWithErrorBagAttribute::class);

        $exception = $this->catchException(ValidationException::class, function () use ($request) {
            $request->validateResolved();
        });

        $this->assertSame('login', $exception->errorBag);
    }

    public function testValidateMethodThrowsWhenAuthorizationFails()
    {
        $this->expectExceptionObject(new AuthorizationException('This action is unauthorized.'));

        $this->createRequest([], FoundationTestFormRequestForbiddenStub::class)->validateResolved();
    }

    public function testValidateThrowsExceptionFromAuthorizationResponse()
    {
        $this->expectExceptionObject(new AuthorizationException('foo'));

        $this->createRequest([], FoundationTestFormRequestForbiddenWithResponseStub::class)->validateResolved();
    }

    public function testValidateDoesntThrowExceptionFromResponseAllowed()
    {
        $this->createRequest([], FoundationTestFormRequestPassesWithResponseStub::class)->validateResolved();
    }

    public function testPrepareForValidationRunsBeforeValidation()
    {
        $this->createRequest([], FoundationTestFormRequestHooks::class)->validateResolved();
    }

    public function testAfterValidationRunsAfterValidation()
    {
        $request = $this->createRequest([], FoundationTestFormRequestHooks::class);

        $request->validateResolved();

        $this->assertEquals(['name' => 'Adam'], $request->all());
    }

    public function testValidatedMethodReturnsOnlyRequestedValidatedData()
    {
        $request = $this->createRequest(['name' => 'specified', 'with' => 'extras']);

        $request->validateResolved();

        $this->assertSame('specified', $request->validated('name'));
    }

    public function testValidatedMethodReturnsOnlyRequestedNestedValidatedData()
    {
        $payload = ['nested' => ['foo' => 'bar', 'baz' => ''], 'array' => [1, 2]];

        $request = $this->createRequest($payload, FoundationTestFormRequestNestedStub::class);

        $request->validateResolved();

        $this->assertSame('bar', $request->validated('nested.foo'));
    }

    public function testAfterMethod()
    {
        $request = new class extends FormRequest
        {
            public $value = 'value-from-request';

            public function rules()
            {
                return [];
            }

            protected function failedValidation(Validator $validator)
            {
                throw new class($validator) extends Exception
                {
                    public function __construct(public $validator)
                    {
                        //
                    }
                };
            }

            public function after(InjectedDependency $dep)
            {
                return [
                    new AfterValidationRule($dep->value),
                    new InvokableAfterValidationRule($this->value),
                    fn ($validator) => $validator->errors()->add('closure', 'true'),
                ];
            }
        };
        $request->setContainer($container = new Container);
        $container->instance(\Illuminate\Contracts\Validation\Factory::class, (new \Illuminate\Validation\Factory(
            new \Illuminate\Translation\Translator(new \Illuminate\Translation\ArrayLoader(), 'en')
        ))->setContainer($container));
        $container->instance(InjectedDependency::class, new InjectedDependency('value-from-dependency'));

        $messages = [];

        try {
            $request->validateResolved();
            $this->fail();
        } catch (Exception $e) {
            if (property_exists($e, 'validator')) {
                $messages = $e->validator->messages()->messages();
            }
        }

        $this->assertSame([
            'after' => ['value-from-dependency'],
            'invokable' => ['value-from-request'],
            'closure' => ['true'],
        ], $messages);
    }

    public function testRequestCanPassWithoutRulesMethod()
    {
        $request = $this->createRequest([], FoundationTestFormRequestWithoutRulesMethod::class);

        $request->validateResolved();

        $this->assertSame([], $request->all());
    }

    public function testRequestWithGetRules()
    {
        FoundationTestFormRequestWithGetRules::$useRuleSet = 'a';
        $request = $this->createRequest(['a' => 1], FoundationTestFormRequestWithGetRules::class);

        $request->validateResolved();
        $this->assertEquals(['a' => 1], $request->all());

        $this->expectException(ValidationException::class);
        FoundationTestFormRequestWithGetRules::$useRuleSet = 'b';

        $request = $this->createRequest(['a' => 1], FoundationTestFormRequestWithGetRules::class);

        $request->validateResolved();
    }

    public function testFailOnUnknownFieldsRejectsExtraInputWhenEnabledOnRequest()
    {
        $request = $this->createRequest(
            ['name' => 'Taylor', 'unexpected' => 'value'],
            FoundationTestFormRequestFailOnUnknownFieldsStub::class,
            'POST'
        );

        $exception = $this->catchException(ValidationException::class, function () use ($request) {
            $request->validateResolved();
        });

        $this->assertTrue($exception->validator->errors()->has('unexpected'));
    }

    public function testFailOnUnknownFieldsAllowsExtraInputWhenExplicitlyDisabledOnRequest()
    {
        $request = $this->createRequest(
            ['name' => 'Taylor', 'with' => 'extras'],
            FoundationTestFormRequestSkipUnknownFieldsFailureStub::class,
            'POST'
        );

        $request->validateResolved();

        $this->assertEquals(['name' => 'Taylor'], $request->validated());
    }

    public function testFailOnUnknownFieldsEnabledViaFailOnUnknownFieldsStaticMethod()
    {
        FormRequest::failOnUnknownFields();

        $request = $this->createRequest(
            ['name' => 'Taylor', 'unexpected' => 'value'],
            FoundationTestFormRequestStub::class,
            'POST'
        );

        $exception = $this->catchException(ValidationException::class, function () use ($request) {
            $request->validateResolved();
        });

        $this->assertTrue($exception->validator->errors()->has('unexpected'));
    }

    public function testFailOnUnknownFieldsWorksWhenRequestDoesNotDefineRulesMethod()
    {
        FormRequest::failOnUnknownFields();

        $request = $this->createRequest(
            ['unexpected' => 'value'],
            FoundationTestFormRequestWithoutRulesMethod::class,
            'POST'
        );

        $exception = $this->catchException(ValidationException::class, function () use ($request) {
            $request->validateResolved();
        });

        $this->assertTrue($exception->validator->errors()->has('unexpected'));
    }

    public function testFailOnUnknownFieldsAttributeOverridesGlobalStatic()
    {
        FormRequest::failOnUnknownFields();

        $request = $this->createRequest(
            ['name' => 'Taylor', 'with' => 'extras'],
            FoundationTestFormRequestSkipUnknownFieldsFailureStub::class,
            'POST'
        );

        $request->validateResolved();

        $this->assertEquals(['name' => 'Taylor'], $request->validated());
    }

    public function testFailOnUnknownFieldsAllowsKeysMatchingWildcardRules()
    {
        $request = $this->createRequest(
            [
                'items' => [
                    ['id' => 1, 'name' => 'a'],
                    ['id' => 2, 'name' => 'b'],
                ],
            ],
            FoundationTestFormRequestFailOnUnknownFieldsWithWildcardStub::class,
            'POST'
        );

        $exception = $this->catchException(ValidationException::class, function () use ($request) {
            $request->validateResolved();
        });

        $this->assertTrue($exception->validator->errors()->has('items.0.name'));
    }

    public function testFailOnUnknownFieldsPassesForInputMatchingWildcardRulesOnly()
    {
        $request = $this->createRequest(
            [
                'items' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ],
            FoundationTestFormRequestFailOnUnknownFieldsWithWildcardStub::class,
            'POST'
        );

        $request->validateResolved();

        $this->assertSame(
            [
                'items' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ],
            $request->validated()
        );
    }

    public function testFailOnUnknownFieldsWildcardMatchesSingleSegmentOnly()
    {
        $request = $this->createRequest(
            [
                'items' => [
                    ['name' => 'a'],
                ],
            ],
            FoundationTestFormRequestFailOnUnknownFieldsSingleSegmentWildcardStub::class,
            'POST'
        );

        $exception = $this->catchException(ValidationException::class, function () use ($request) {
            $request->validateResolved();
        });

        $this->assertTrue($exception->validator->errors()->has('items.0.name'));
    }

    public function testFailOnUnknownFieldsRejectsMultipleUnknownKeys()
    {
        $request = $this->createRequest(
            [
                'name' => 'Taylor',
                'role' => 'admin',
                'profile' => ['is_admin' => true],
            ],
            FoundationTestFormRequestFailOnUnknownFieldsStub::class,
            'POST'
        );

        $exception = $this->catchException(ValidationException::class, function () use ($request) {
            $request->validateResolved();
        });

        $this->assertTrue($exception->validator->errors()->has('role'));
        $this->assertTrue($exception->validator->errors()->has('profile.is_admin'));
    }

    public function testFailOnUnknownFieldsRejectsUnknownNestedSibling()
    {
        $request = $this->createRequest(
            ['user' => ['name' => 'Taylor', 'role' => 'admin']],
            FoundationTestFormRequestFailOnUnknownFieldsNestedStub::class,
            'POST'
        );

        $exception = $this->catchException(ValidationException::class, function () use ($request) {
            $request->validateResolved();
        });

        $this->assertTrue($exception->validator->errors()->has('user.role'));
    }

    public function testFailOnUnknownFieldsUsesPreparedInput()
    {
        $request = $this->createRequest(
            ['full_name' => 'Taylor'],
            FoundationTestFormRequestFailOnUnknownFieldsPrepareForValidationStub::class,
            'POST'
        );

        $request->validateResolved();

        $this->assertSame(['name' => 'Taylor'], $request->validated());
    }

    public function testFailOnUnknownFieldsChecksRequestPayloadWhenValidationDataIsOverridden()
    {
        $request = $this->createRequest(
            ['name' => 'Taylor', 'unexpected' => 'value'],
            FoundationTestFormRequestFailOnUnknownFieldsValidationDataOverrideStub::class,
            'POST'
        );

        $exception = $this->catchException(ValidationException::class, function () use ($request) {
            $request->validateResolved();
        });

        $this->assertTrue($exception->validator->errors()->has('unexpected'));
    }

    public function testFailOnUnknownFieldsStillRunsWithStopOnFirstFailureAttribute()
    {
        $request = $this->createRequest(
            ['unexpected' => 'value'],
            FoundationTestFormRequestFailOnUnknownFieldsStopOnFirstFailureStub::class,
            'POST'
        );

        $exception = $this->catchException(ValidationException::class, function () use ($request) {
            $request->validateResolved();
        });

        $this->assertTrue($exception->validator->errors()->has('unexpected'));
    }

    public function testFailOnUnknownFieldsIgnoresQueryParametersOnGetRequests()
    {
        FormRequest::failOnUnknownFields();

        $container = tap(new Container, function ($container) {
            $container->instance(
                ValidationFactoryContract::class,
                $this->createValidationFactory($container)
            );

            $container->instance('translator', new TranslatorConcrete(new ArrayLoader([
                'validation' => [
                    'prohibited' => 'The :attribute field is prohibited.',
                ],
            ]), 'en'));
        });

        Container::setInstance($container);

        $request = FoundationTestFormRequestWithoutRulesMethod::create(
            '/?page=1&perPage=5&expires=1234567890&signature=abc123',
            'GET'
        );

        $request->setRedirector($this->createMockRedirector($request))
            ->setContainer($container);

        $request->validateResolved();

        $this->assertSame([], $request->validated());
    }

    public function testFailOnUnknownFieldsAllowsConfirmationFieldsWhenBaseFieldIsConfirmed()
    {
        FormRequest::failOnUnknownFields();

        $container = tap(new Container, function ($container) {
            $container->instance(
                ValidationFactoryContract::class,
                $this->createValidationFactory($container)
            );

            $container->instance('translator', new TranslatorConcrete(new ArrayLoader([
                'validation' => [
                    'prohibited' => 'The :attribute field is prohibited.',
                ],
            ]), 'en'));
        });

        Container::setInstance($container);

        $request = FoundationTestFormRequestConfirmedFieldStub::create(
            '/',
            'POST',
            ['password' => 'secret123', 'password_confirmation' => 'secret123']
        );

        $request->setRedirector($this->createMockRedirector($request))
            ->setContainer($container);

        $request->validateResolved();

        $this->assertEquals(['password' => 'secret123'], $request->validated());
    }

    // public function testFailOnUnknownFieldsRejectsConfirmationFieldsWithoutConfirmedRule()
    // {
    //     FormRequest::failOnUnknownFields();

    //     $container = tap(new Container, function ($container) {
    //         $container->instance(
    //             ValidationFactoryContract::class,
    //             $this->createValidationFactory($container)
    //         );

    //         $container->instance('translator', new TranslatorConcrete(new ArrayLoader([
    //             'validation' => [
    //                 'prohibited' => 'The :attribute field is prohibited.',
    //             ],
    //         ]), 'en'));
    //     });

    //     Container::setInstance($container);

    //     $request = FoundationTestFormRequestUnconfirmedFieldStub::create(
    //         '/',
    //         'POST',
    //         ['password' => 'secret123', 'password_confirmation' => 'secret123']
    //     );

    //     $request->setRedirector($this->createMockRedirector($request))
    //         ->setContainer($container);

    //     $exception = $this->catchException(ValidationException::class, function () use ($request) {
    //         $request->validateResolved();
    //     });

    //     $this->assertTrue($exception->validator->errors()->has('password_confirmation'));
    // }

    /**
     * Catch the given exception thrown from the executor, and return it.
     *
     * @param  string  $class
     * @param  \Closure  $executor
     * @return \Exception
     *
     * @throws \Exception
     */
    protected function catchException($class, $executor)
    {
        try {
            $executor();
        } catch (Exception $e) {
            if (is_a($e, $class)) {
                return $e;
            }

            throw $e;
        }

        throw new Exception("No exception thrown. Expected exception {$class}.");
    }

    /**
     * Create a new request of the given type.
     *
     * @param  array  $payload
     * @param  string  $class
     * @return \Illuminate\Foundation\Http\FormRequest
     */
    protected function createRequest($payload = [], $class = FoundationTestFormRequestStub::class, $method = 'GET')
    {
        $container = tap(new Container, function ($container) {
            $container->instance(
                ValidationFactoryContract::class,
                $this->createValidationFactory($container)
            );

            $container->instance('translator', new TranslatorConcrete(new ArrayLoader([
                'validation' => [
                    'prohibited' => 'The :attribute field is prohibited.',
                ],
            ]), 'en'));
        });

        Container::setInstance($container);

        $request = $class::create('/', $method, $payload);

        return $request->setRedirector($this->createMockRedirector($request))
            ->setContainer($container);
    }

    /**
     * Create a new validation factory.
     *
     * @param  \Illuminate\Container\Container  $container
     * @return \Illuminate\Validation\Factory
     */
    protected function createValidationFactory($container)
    {
        $translator = m::mock(Translator::class)->shouldReceive('get')
            ->zeroOrMoreTimes()->andReturn('error')->shouldReceive('choice')
            ->zeroOrMoreTimes()->andReturn('error')->getMock();

        return new ValidationFactory($translator, $container);
    }

    /**
     * Create a mock redirector.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Redirector
     */
    protected function createMockRedirector($request)
    {
        $redirector = $this->mocks['redirector'] = m::mock(Redirector::class);

        $redirector->shouldReceive('getUrlGenerator')->zeroOrMoreTimes()
            ->andReturn($generator = $this->createMockUrlGenerator());

        $redirector->shouldReceive('to')->zeroOrMoreTimes()
            ->andReturn($this->createMockRedirectResponse());

        $generator->shouldReceive('previous')->zeroOrMoreTimes()
            ->andReturn('previous');

        return $redirector;
    }

    /**
     * Create a mock URL generator.
     *
     * @return \Illuminate\Routing\UrlGenerator
     */
    protected function createMockUrlGenerator()
    {
        return $this->mocks['generator'] = m::mock(UrlGenerator::class);
    }

    /**
     * Create a mock redirect response.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function createMockRedirectResponse()
    {
        return $this->mocks['redirect'] = m::mock(RedirectResponse::class);
    }
}

class FoundationTestFormRequestStub extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required'];
    }

    public function authorize()
    {
        return true;
    }
}

class FoundationTestFormRequestNestedStub extends FormRequest
{
    public function rules()
    {
        return ['nested.foo' => 'required', 'array.*' => 'integer'];
    }

    public function authorize()
    {
        return true;
    }
}

class FoundationTestFormRequestNestedChildStub extends FormRequest
{
    public function rules()
    {
        return ['nested.foo' => 'required'];
    }

    public function authorize()
    {
        return true;
    }
}

class FoundationTestFormRequestNestedArrayStub extends FormRequest
{
    public function rules()
    {
        return ['nested.*.bar' => 'required'];
    }

    public function authorize()
    {
        return true;
    }
}

class FoundationTestFormRequestTwiceStub extends FormRequest
{
    public static $count = 0;

    public function rules()
    {
        return ['name' => 'required'];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            self::$count++;
        });
    }

    public function authorize()
    {
        return true;
    }
}

class FoundationTestFormRequestForbiddenStub extends FormRequest
{
    public function authorize()
    {
        return false;
    }
}

class FoundationTestFormRequestHooks extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required'];
    }

    public function authorize()
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->replace(['name' => 'Taylor']);
    }

    public function passedValidation()
    {
        $this->replace(['name' => 'Adam']);
    }
}

class FoundationTestFormRequestForbiddenWithResponseStub extends FormRequest
{
    public function authorize()
    {
        return Response::deny('foo');
    }
}

class FoundationTestFormRequestPassesWithResponseStub extends FormRequest
{
    public function rules()
    {
        return [];
    }

    public function authorize()
    {
        return Response::allow('baz');
    }
}

#[ErrorBag('login')]
class FoundationTestFormRequestWithErrorBagAttribute extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required'];
    }

    public function authorize()
    {
        return true;
    }
}

class InvokableAfterValidationRule
{
    public function __construct(private $value)
    {
    }

    public function __invoke($validator)
    {
        $validator->errors()->add('invokable', $this->value);
    }
}

class AfterValidationRule
{
    public function __construct(private $value)
    {
        //
    }

    public function after($validator)
    {
        $validator->errors()->add('after', $this->value);
    }
}

class InjectedDependency
{
    public function __construct(public $value)
    {
        //
    }
}

class FoundationTestFormRequestWithoutRulesMethod extends FormRequest
{
    public function authorize()
    {
        return true;
    }
}

class FoundationTestFormRequestWithGetRules extends FormRequest
{
    public static $useRuleSet = 'a';

    protected function validationRules(): array
    {
        if (self::$useRuleSet === 'a') {
            return [
                'a' => ['required', 'int', 'min:1'],
            ];
        } else {
            return [
                'a' => ['required', 'int', 'min:2'],
            ];
        }
    }
}

#[FailOnUnknownFields]
class FoundationTestFormRequestFailOnUnknownFieldsStub extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required'];
    }

    public function authorize()
    {
        return true;
    }
}

#[FailOnUnknownFields(false)]
class FoundationTestFormRequestSkipUnknownFieldsFailureStub extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required'];
    }

    public function authorize()
    {
        return true;
    }
}

#[FailOnUnknownFields]
class FoundationTestFormRequestFailOnUnknownFieldsWithWildcardStub extends FormRequest
{
    public function rules()
    {
        return ['items.*.id' => 'required'];
    }

    public function authorize()
    {
        return true;
    }
}

#[FailOnUnknownFields]
class FoundationTestFormRequestFailOnUnknownFieldsSingleSegmentWildcardStub extends FormRequest
{
    public function rules()
    {
        return ['items.*' => 'array'];
    }

    public function authorize()
    {
        return true;
    }
}

#[FailOnUnknownFields]
class FoundationTestFormRequestFailOnUnknownFieldsNestedStub extends FormRequest
{
    public function rules()
    {
        return ['user.name' => 'required'];
    }

    public function authorize()
    {
        return true;
    }
}

#[FailOnUnknownFields]
class FoundationTestFormRequestFailOnUnknownFieldsPrepareForValidationStub extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required'];
    }

    public function prepareForValidation()
    {
        $this->replace(['name' => $this->input('full_name')]);
    }

    public function authorize()
    {
        return true;
    }
}

#[FailOnUnknownFields]
class FoundationTestFormRequestFailOnUnknownFieldsValidationDataOverrideStub extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required'];
    }

    public function validationData()
    {
        return ['name' => $this->input('name')];
    }

    public function authorize()
    {
        return true;
    }
}

#[StopOnFirstFailure]
#[FailOnUnknownFields]
class FoundationTestFormRequestFailOnUnknownFieldsStopOnFirstFailureStub extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required'];
    }

    public function authorize()
    {
        return true;
    }
}

class FoundationTestFormRequestConfirmedFieldStub extends FormRequest
{
    public function rules()
    {
        return ['password' => 'required|confirmed'];
    }

    public function authorize()
    {
        return true;
    }
}

class FoundationTestFormRequestUnconfirmedFieldStub extends FormRequest
{
    public function rules()
    {
        return ['password' => 'required'];
    }

    public function authorize()
    {
        return true;
    }
}
