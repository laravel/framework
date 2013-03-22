<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

class FoundationApplicationTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testEnvironmentDetection()
	{
		$app = m::mock('Illuminate\Foundation\Application[runningInConsole]');
		$app['request'] = m::mock('Symfony\Component\HttpFoundation\Request');
		$app['request']->shouldReceive('getHost')->andReturn('foo');
		$app['request']->server = m::mock('StdClass');
		$app['request']->server->shouldReceive('get')->once()->with('argv')->andReturn(array());
		$app->shouldReceive('runningInConsole')->once()->andReturn(false);
		$app->detectEnvironment(array(
			'local'   => array('localhost')
		));
		$this->assertEquals('production', $app['env']);

		$app = m::mock('Illuminate\Foundation\Application[runningInConsole]');
		$app['request'] = m::mock('Symfony\Component\HttpFoundation\Request');
		$app['request']->shouldReceive('getHost')->andReturn('localhost');
		$app['request']->server = m::mock('StdClass');
		$app['request']->server->shouldReceive('get')->once()->with('argv')->andReturn(array());
		$app->shouldReceive('runningInConsole')->once()->andReturn(false);
		$app->detectEnvironment(array(
			'local'   => array('localhost')
		));
		$this->assertEquals('local', $app['env']);

		$app = m::mock('Illuminate\Foundation\Application[runningInConsole]');
		$app['request'] = m::mock('Symfony\Component\HttpFoundation\Request');
		$app['request']->shouldReceive('getHost')->andReturn('localhost');
		$app['request']->server = m::mock('StdClass');
		$app['request']->server->shouldReceive('get')->once()->with('argv')->andReturn(array());
		$app->shouldReceive('runningInConsole')->once()->andReturn(false);
		$app->detectEnvironment(array(
			'local'   => array('local*')
		));
		$this->assertEquals('local', $app['env']);

		$app = m::mock('Illuminate\Foundation\Application[runningInConsole]');
		$app['request'] = m::mock('Symfony\Component\HttpFoundation\Request');
		$app['request']->shouldReceive('getHost')->andReturn('localhost');
		$app['request']->server = m::mock('StdClass');
		$app['request']->server->shouldReceive('get')->once()->with('argv')->andReturn(array());
		$app->shouldReceive('runningInConsole')->once()->andReturn(false);
		$host = gethostname();
		$app->detectEnvironment(array(
			'local'   => array($host)
		));
		$this->assertEquals('local', $app['env']);
	}


	public function testClosureCanBeUsedForCustomEnvironmentDetection()
	{
		$app = m::mock('Illuminate\Foundation\Application[runningInConsole]');
		$app['request'] = m::mock('Symfony\Component\HttpFoundation\Request');
		$app['request']->shouldReceive('getHost')->andReturn('foo');
		$app['request']->server = m::mock('StdClass');
		$app['request']->server->shouldReceive('get')->once()->with('argv')->andReturn(array());
		$app->shouldReceive('runningInConsole')->once()->andReturn(false);
		$app->detectEnvironment(function() { return 'foobar'; });
		$this->assertEquals('foobar', $app['env']);
	}


	public function testConsoleEnvironmentDetection()
	{
		$app = new Application;
		$app['request'] = m::mock('Symfony\Component\HttpFoundation\Request');
		$app['request']->shouldReceive('getHost')->andReturn('foo');
		$app['request']->server = m::mock('StdClass');
		$app['request']->server->shouldReceive('get')->once()->with('argv')->andReturn(array('--env=local'));
		$app->detectEnvironment(array(
			'local'   => array('foobar')
		));
		$this->assertEquals('local', $app['env']);
	}


	public function testPrepareRequestInjectsSession()
	{
		$app = new Application;
		$request = Illuminate\Http\Request::create('/', 'GET');
		$app['session'] = m::mock('Illuminate\Session\Store');
		$app->prepareRequest($request);
		$this->assertEquals($app['session'], $request->getSessionStore());
	}


	public function testExceptionHandlingSendsResponseFromCustomHandler()
	{
		$app = $this->getMock('Illuminate\Foundation\Application', array('prepareResponse'));
		$response = m::mock('stdClass');
		$response->shouldReceive('send')->once();
		$app['request'] = Request::create('/foo', 'GET');
		$app->expects($this->once())->method('prepareResponse')->with($this->equalTo('foo'), $this->equalTo($app['request']))->will($this->returnValue($response));
		$exception = new Exception;
		$errorHandler = m::mock('stdClass');
		$exceptionHandler = m::mock('stdClass');
		$exceptionHandler->shouldReceive('handle')->once()->with($exception)->andReturn('foo');
		$kernelHandler = m::mock('stdClass');
		$kernelHandler->shouldReceive('handle')->never();
		$app['kernel.exception'] = $kernelHandler;
		$app['kernel.error'] = $errorHandler;
		$app['exception'] = $exceptionHandler;
		$handler = $app['exception.function'];
		$handler($exception);
	}


	public function testNoResponseFromCustomHandlerCallsKernelExceptionHandler()
	{
		$app = new Application;
		$exception = new Exception;
		$errorHandler = m::mock('stdClass');
		$exceptionHandler = m::mock('stdClass');
		$exceptionHandler->shouldReceive('handle')->once()->with($exception)->andReturn(null);
		$kernelHandler = m::mock('stdClass');
		$kernelHandler->shouldReceive('handle')->once()->with($exception);
		$app['kernel.exception'] = $kernelHandler;
		$app['kernel.error'] = $errorHandler;
		$app['exception'] = $exceptionHandler;
		$handler = $app['exception.function'];
		$handler($exception);
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

	public function prepareResponse($value, Illuminate\Http\Request $request)
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