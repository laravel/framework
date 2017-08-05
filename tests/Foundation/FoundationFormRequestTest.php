<?php

namespace Illuminate\Tests\Foundation;

use Exception;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Routing\Redirector;
use Illuminate\Container\Container;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Factory as ValidationFactoryContract;

class FoundationFormRequestTest extends TestCase
{
    protected $mocks = [];

    public function tearDown()
    {
        m::close();

        $this->mocks = [];
    }

    public function test_validated_method_returns_the_validated_data()
    {
        $request = $this->createRequest(['name' => 'specified', 'with' => 'extras']);

        $request->validate();

        $this->assertEquals(['name' => 'specified'], $request->validated());
    }

    /**
     * @expectedException \Illuminate\Validation\ValidationException
     */
    public function test_validate_throws_when_validation_fails()
    {
        $request = $this->createRequest(['no' => 'name']);

        $this->mocks['redirect']->shouldReceive('withInput->withErrors');

        $request->validate();
    }

    /**
     * @expectedException \Illuminate\Auth\Access\AuthorizationException
     * @expectedExceptionMessage This action is unauthorized.
     */
    public function test_validate_method_throws_when_authorization_fails()
    {
        $this->createRequest([], FoundationTestFormRequestForbiddenStub::class)->validate();
    }

    public function test_prepare_for_validation_runs_before_validation()
    {
        $this->createRequest([], FoundationTestFormRequestHooks::class)->validate();
    }

    /**
     * Catch the given exception thrown from the executor, and return it.
     *
     * @param  string  $class
     * @param  \Closure  $excecutor
     * @return \Exception
     */
    protected function catchException($class, $excecutor)
    {
        try {
            $excecutor();
        } catch (Exception $e) {
            if (is_a($e, $class)) {
                return $e;
            }

            throw $e;
        }

        throw new Exception("No exception thrown. Expected exception {$class}");
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
        $translator = m::mock(Translator::class)->shouldReceive('trans')
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

class FoundationTestFormRequestForbiddenStub extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required'];
    }

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
}
