<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;

class FoundationFormRequestTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testValidateFunctionRunsValidatorOnSpecifiedRules()
    {
        $request = FoundationTestFormRequestStub::create('/', 'GET', ['name' => 'abigail']);
        $request->setContainer($container = new Container);
        $factory = m::mock('Illuminate\Validation\Factory');
        $factory->shouldReceive('make')->once()->with(['name' => 'abigail'], ['name' => 'required'], [], [])->andReturn(
            $validator = m::mock('Illuminate\Validation\Validator')
        );
        $container->instance('Illuminate\Contracts\Validation\Factory', $factory);
        $validator->shouldReceive('passes')->once()->andReturn(true);

        $request->validate($factory);
    }

    /**
     * @expectedException \Illuminate\Validation\ValidationException
     */
    public function testValidateFunctionThrowsValidationExceptionIfValidationFails()
    {
        $request = m::mock('Illuminate\Tests\Foundation\FoundationTestFormRequestStub[response]');
        $request->initialize(['name' => null]);
        $request->setContainer($container = new Container);
        $factory = m::mock('Illuminate\Validation\Factory');
        $factory->shouldReceive('make')->once()->with(['name' => null], ['name' => 'required'], [], [])->andReturn(
            $validator = m::mock('Illuminate\Validation\Validator')
        );
        $container->instance('Illuminate\Contracts\Validation\Factory', $factory);
        $validator->shouldReceive('passes')->once()->andReturn(false);
        $validator->shouldReceive('getMessageBag')->once()->andReturn($messages = m::mock('Illuminate\Support\MessageBag'));
        $messages->shouldReceive('toArray')->once()->andReturn(['name' => ['Name required']]);
        $request->shouldReceive('response')->once()->andReturn(new \Illuminate\Http\Response);

        $request->validate($factory);
    }

    /**
     * @expectedException \Illuminate\Auth\Access\AuthorizationException
     */
    public function testValidateFunctionThrowsHttpResponseExceptionIfAuthorizationFails()
    {
        $request = m::mock('Illuminate\Tests\Foundation\FoundationTestFormRequestForbiddenStub[forbiddenResponse]');
        $request->initialize(['name' => null]);
        $request->setContainer($container = new Container);
        $factory = m::mock('Illuminate\Validation\Factory');
        $factory->shouldReceive('make')->once()->with(['name' => null], ['name' => 'required'], [], [])->andReturn(
            $validator = m::mock('Illuminate\Validation\Validator')
        );
        $container->instance('Illuminate\Contracts\Validation\Factory', $factory);
        $validator->shouldReceive('passes')->never();

        $request->validate($factory);
    }

    public function testRedirectResponseIsProperlyCreatedWithGivenErrors()
    {
        $request = FoundationTestFormRequestStub::create('/', 'GET');
        $request->setRedirector($redirector = m::mock('Illuminate\Routing\Redirector'));
        $redirector->shouldReceive('to')->once()->with('previous')->andReturn($response = m::mock('Illuminate\Http\RedirectResponse'));
        $redirector->shouldReceive('getUrlGenerator')->andReturn($url = m::mock('StdClass'));
        $url->shouldReceive('previous')->once()->andReturn('previous');
        $response->shouldReceive('withInput')->andReturn($response);
        $response->shouldReceive('withErrors')->with(['errors'], 'default')->andReturn($response);

        $request->response(['errors']);
    }

    public function testValidateFunctionRunsBeforeValidationFunction()
    {
        $request = FoundationTestFormRequestHooks::create('/', 'GET', ['name' => 'abigail']);
        $request->setContainer($container = new Container);
        $factory = m::mock('Illuminate\Validation\Factory');
        $factory->shouldReceive('make')->once()->with(['name' => 'Taylor'], ['name' => 'required'], [], [])->andReturn(
            $validator = m::mock('Illuminate\Validation\Validator')
        );
        $container->instance('Illuminate\Contracts\Validation\Factory', $factory);
        $validator->shouldReceive('passes')->once()->andReturn(true);

        $request->validate($factory);
    }
}

class FoundationTestFormRequestStub extends \Illuminate\Foundation\Http\FormRequest
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

class FoundationTestFormRequestForbiddenStub extends \Illuminate\Foundation\Http\FormRequest
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
class FoundationTestFormRequestHooks extends \Illuminate\Foundation\Http\FormRequest
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
