<?php

namespace Illuminate\Tests\Foundation;

use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactoryContract;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FoundationFormRequestTest extends TestCase
{
    protected $mocks = [];

    protected function tearDown(): void
    {
        m::close();

        $this->mocks = [];
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

    public function testValidateMethodThrowsWhenAuthorizationFails()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('This action is unauthorized.');

        $this->createRequest([], FoundationTestFormRequestForbiddenStub::class)->validateResolved();
    }

    public function testValidateThrowsExceptionFromAuthorizationResponse()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('foo');

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

        $this->assertEquals([], $request->all());
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

    public function testRequestMapToStringClass()
    {
        $request = $this->createRequest(['email' => 'test@laravel.com', 'name' => 'Taylor']);
        $user = $request->mapTo(DummyUser::class);

        $this->assertInstanceOf(DummyUser::class, $user);
        $this->assertEquals('Taylor', $user->name);
        $this->assertEquals('test@laravel.com', $user->email);
    }

    public function testRequestMapToClassInstance()
    {
        $request = $this->createRequest(['email' => 'test@laravel.com', 'name' => 'Taylor']);
        $user = new DummyUser();
        $user = $request->mapTo($user);

        $this->assertInstanceOf(DummyUser::class, $user);
        $this->assertEquals('Taylor', $user->name);
        $this->assertEquals('test@laravel.com', $user->email);
    }

    public function testRequestMapToModel()
    {
        $request = $this->createRequest(['email' => 'test@laravel.com', 'name' => 'Taylor']);
        $user = $request->mapTo(DummyUserModel::class);

        $this->assertInstanceOf(DummyUserModel::class, $user);
        $this->assertEquals('Taylor', $user->name);
        $this->assertEquals('test@laravel.com', $user->email);
    }

    public function testRequestMapToCamelCase()
    {
        $request = $this->createRequest(['user_name' => 'taylor']);
        $user = $request->mapTo(DummyUserNameCamel::class);

        $this->assertInstanceOf(DummyUserNameCamel::class, $user);
        $this->assertEquals('taylor', $user->userName);
    }

    public function testRequestMapToSnakeCase()
    {
        $request = $this->createRequest(['userName' => 'taylor']);
        $user = $request->mapTo(DummyUserNameSnake::class);

        $this->assertInstanceOf(DummyUserNameSnake::class, $user);
        $this->assertEquals('taylor', $user->user_name);
    }

    public function testRequestMapNestedData()
    {
        $request = $this->createRequest([
            'email' => 'test@laravel.com',
            'username' => ['user_name' => 'taylor'],
            'list' => [1, 2, 3],
            'date' => '2025-06-12',
        ]);
        $user = $request->mapTo(DummyUserNested::class);

        $this->assertInstanceOf(DummyUserNested::class, $user);
        $this->assertEquals('test@laravel.com', $user->email);
        $this->assertInstanceOf(DummyUserNameCamel::class, $user->username);
        $this->assertEquals('taylor', $user->username->userName);
        $this->assertInstanceOf(Collection::class, $user->list);
        $this->assertEquals(collect([1, 2, 3]), $user->list);
        $this->assertInstanceOf(Carbon::class, $user->date);
        $this->assertEquals('2025-06-12', $user->date->format('Y-m-d'));
    }

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
    protected function createRequest($payload = [], $class = FoundationTestFormRequestStub::class)
    {
        $container = tap(new Container, function ($container) {
            $container->instance(
                ValidationFactoryContract::class,
                $this->createValidationFactory($container)
            );
        });

        $request = $class::create('/', 'GET', $payload);

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

class DummyUser
{
    public string $email;

    public string $name;
}

class DummyUserModel extends Model
{
    protected $fillable = ['email', 'name'];
}

class DummyUserNameCamel
{
    public string $userName;
}

class DummyUserNameSnake
{
    public $user_name;
}

class DummyUserNested
{
    public string $email;

    public DummyUserNameCamel $username;

    public Collection $list;

    public Carbon $date;
}
