<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

class FoundationApplicationTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPrepareRequestInjectsSession()
	{
		$app = new Application;
		$request = Illuminate\Http\Request::create('/', 'GET');
		$app['session.store'] = m::mock('Illuminate\Session\Store');
		$app->prepareRequest($request);
		$this->assertEquals($app['session.store'], $request->getSessionStore());
	}


	public function testSetLocaleSetsLocaleAndFiresLocaleChangedEvent()
	{
		$app = new Application;
		$app['config'] = $config = m::mock('StdClass');
		$config->shouldReceive('set')->once()->with('app.locale', 'foo');
		$app['translator'] = $trans = m::mock('StdClass');
		$trans->shouldReceive('setLocale')->once()->with('foo');
		$app['events'] = $events = m::mock('StdClass');
		$events->shouldReceive('fire')->once()->with('locale.changed', array('foo'));

		$app->setLocale('foo');
	}


	public function testServiceProvidersAreCorrectlyRegistered()
	{
		$provider = m::mock('Illuminate\Support\ServiceProvider');
		$class = get_class($provider);
		$provider->shouldReceive('register')->once();
		$app = new Application;
		$app->register($provider);

		$this->assertTrue(in_array($class, $app->getLoadedProviders()));
	}

}

class ApplicationCustomExceptionHandlerStub extends Illuminate\Foundation\Application {

	public function prepareResponse($value)
	{
		$response = m::mock('Symfony\Component\HttpFoundation\Response');
		$response->shouldReceive('send')->once();
		return $response;
	}

	protected function setExceptionHandler(Closure $handler) { return $handler; }

}

class ApplicationKernelExceptionHandlerStub extends Illuminate\Foundation\Application {

	protected function setExceptionHandler(Closure $handler) { return $handler; }

}