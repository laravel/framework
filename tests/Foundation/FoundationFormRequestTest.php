<?php

use Mockery as m;
use Illuminate\Container\Container;

class FoundationFormRequestTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
		unset($_SERVER['__request.validated']);
	}


	public function testValidateFunctionRunsValidatorOnSpecifiedRules()
	{
		$request = FoundationTestFormRequestStub::create('/', 'GET', ['name' => 'abigail']);
		$container = new Container();
		$factory = m::mock('Illuminate\Validation\Factory');
		$validator = m::mock('Illuminate\Validation\Validator');
		$validator->shouldReceive('passes')->once()->andReturn(true);
		$container->bind('Illuminate\Validation\Factory', function() use ($factory, $validator)
		{
			$factory->shouldReceive('make')->once()->with(['name' => 'abigail'], ['name' => 'required'], [])->andReturn($validator);
			return $factory;
		});
		$request->setContainer($container);

		$request->validate($factory);
	}


	public function testValidateFunctionRunsValidatorOnExtendedRules()
	{
		$request = FoundationTestFormRequestStubExtended::create('/', 'GET', ['name' => 'abigail']);
		$container = new Container();
		$factory = m::mock('Illuminate\Validation\Factory');
		$validator = m::mock('Illuminate\Validation\Validator');
		$validator->shouldReceive('passes')->once()->andReturn(true);
		$container->bind('Illuminate\Validation\Factory', function() use ($factory, $validator)
		{
			$factory->shouldReceive('make')->once()->with(['name' => 'abigail'], ['name' => 'foo'], [])->andReturn($validator);
			$factory->shouldReceive('extend')->once()->with('foo', true);
			return $factory;
		});
		$request->setContainer($container);

		$request->validate($factory);
	}


	/**
	 * @expectedException \Illuminate\Http\Exception\HttpResponseException
	 */
	public function testValidateFunctionThrowsHttpResponseExceptionIfValidationFails()
	{
		$request = m::mock('FoundationTestFormRequestStub[response]');
		$request->initialize(['name' => null]);
		$container = new Container();
		$factory = m::mock('Illuminate\Validation\Factory');
		$validator = m::mock('Illuminate\Validation\Validator');
		$validator->shouldReceive('passes')->once()->andReturn(false);
		$validator->shouldReceive('errors')->once()->andReturn($messages = m::mock('StdClass'));
		$messages->shouldReceive('getMessages')->once()->andReturn([]);
		$container->bind('Illuminate\Validation\Factory', function() use ($factory, $validator)
		{
			$factory->shouldReceive('make')->once()->with(['name' => null], ['name' => 'required'], [])->andReturn($validator);
			return $factory;
		});
		$request->shouldReceive('response')->once()->andReturn(new Illuminate\Http\Response);
		$request->setContainer($container);

		$request->validate($factory);
	}


	/**
	 * @expectedException \Illuminate\Http\Exception\HttpResponseException
	 */
	public function testValidateFunctionThrowsHttpResponseExceptionIfAuthorizationFails()
	{
		$request = m::mock('FoundationTestFormRequestForbiddenStub[forbiddenResponse]');
		$request->initialize(['name' => null]);
		$container = new Container();
		$factory = m::mock('Illuminate\Validation\Factory');
		$validator = m::mock('Illuminate\Validation\Validator');
		$container->bind('Illuminate\Validation\Factory', function() use ($factory, $validator)
		{
			$factory->shouldReceive('make')->once()->with(['name' => null], ['name' => 'required'], [])->andReturn($validator);
			return $factory;
		});
		$request->setContainer($container);
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
		$response->shouldReceive('withErrors')->with(['errors'], 'default')->andReturn($response);

		$request->response(['errors']);
	}

}

class FoundationTestFormRequestStub extends Illuminate\Foundation\Http\FormRequest {
	public function rules(StdClass $dep) {
		return ['name' => 'required'];
	}
	public function authorize(StdClass $dep) {
		return true;
	}
}

class FoundationTestFormRequestForbiddenStub extends Illuminate\Foundation\Http\FormRequest {
	public function rules() {
		return ['name' => 'required'];
	}
	public function authorize() {
		return false;
	}
}

class FoundationTestFormRequestStubExtended extends Illuminate\Foundation\Http\FormRequest {
	public function rules(StdClass $dep) {
		return ['name' => 'foo'];
	}
	public function authorize(StdClass $dep) {
		return true;
	}
	public function extend(Illuminate\Validation\Factory $factory) {
		$factory->extend('foo', true);
	}
}
