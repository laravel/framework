<?php

use Mockery as m;
use Illuminate\Container\Container;

class FoundationFormRequestTestCase extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
        unset($_SERVER['__request.validated']);
    }

    public function testValidateFunctionRunsValidatorOnSpecifiedRules()
    {
        $request = FoundationTestFormRequestStub::create('/', 'GET', ['name' => 'abigail']);
        $request->setContainer(new Container);
        $factory = m::mock('Illuminate\Validation\Factory');
        $factory->shouldReceive('make')->once()->with(['name' => 'abigail'], ['name' => 'required'])->andReturn(
            $validator = m::mock('Illuminate\Validation\Validator')
        );
        $validator->shouldReceive('fails')->once()->andReturn(false);

        $request->validate($factory);

        $this->assertTrue($_SERVER['__request.validated']);
    }

    /**
     * @expectedException \Illuminate\Http\Exception\HttpResponseException
     */
    public function testValidateFunctionThrowsHttpResponseExceptionIfValidationFails()
    {
        $request = m::mock('FoundationTestFormRequestStub[response]');
        $request->initialize(['name' => null]);
        $request->setContainer(new Container);
        $factory = m::mock('Illuminate\Validation\Factory');
        $factory->shouldReceive('make')->once()->with(['name' => null], ['name' => 'required'])->andReturn(
            $validator = m::mock('Illuminate\Validation\Validator')
        );
        $validator->shouldReceive('fails')->once()->andReturn(true);
        $validator->shouldReceive('errors')->once()->andReturn($messages = m::mock('StdClass'));
        $messages->shouldReceive('all')->once()->andReturn([]);
        $request->shouldReceive('response')->once()->andReturn(new Illuminate\Http\Response);

        $request->validate($factory);
    }

    /**
     * @expectedException \Illuminate\Http\Exception\HttpResponseException
     */
    public function testValidateFunctionThrowsHttpResponseExceptionIfAuthorizationFails()
    {
        $request = m::mock('FoundationTestFormRequestForbiddenStub[forbiddenResponse]');
        $request->initialize(['name' => null]);
        $request->setContainer(new Container);
        $factory = m::mock('Illuminate\Validation\Factory');
        $factory->shouldReceive('make')->once()->with(['name' => null], ['name' => 'required'])->andReturn(
            $validator = m::mock('Illuminate\Validation\Validator')
        );
        $validator->shouldReceive('fails')->once()->andReturn(false);
        $request->shouldReceive('forbiddenResponse')->once()->andReturn(new Illuminate\Http\Response);

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
        $response->shouldReceive('withErrors')->with(['errors'])->andReturn($response);

        $request->response(['errors']);
    }
}

class FoundationTestFormRequestStub extends Illuminate\Foundation\Http\FormRequest
{
    public function rules(StdClass $dep)
    {
        return ['name' => 'required'];
    }

    public function authorize(StdClass $dep)
    {
        return true;
    }

    public function validated(StdClass $dep)
    {
        $_SERVER['__request.validated'] = true;
    }
}

class FoundationTestFormRequestForbiddenStub extends Illuminate\Foundation\Http\FormRequest
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
