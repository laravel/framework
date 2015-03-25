<?php namespace Illuminate\Foundation\Testing;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

trait ApplicationTrait {

	/**
	 * The Illuminate application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * The last response returned by the application.
	 *
	 * @var \Illuminate\Http\Response
	 */
	protected $response;

	/**
	 * The last code returned by artisan cli.
	 *
	 * @var int
	 */
	protected $code;

	/**
	 * Refresh the application instance.
	 *
	 * @return void
	 */
	protected function refreshApplication()
	{
		putenv('APP_ENV=testing');

		$this->app = $this->createApplication();
	}

	/**
	 * Call the given URI and return the Response.
	 *
	 * @param  string  $method
	 * @param  string  $uri
	 * @param  array   $parameters
	 * @param  array   $cookies
	 * @param  array   $files
	 * @param  array   $server
	 * @param  string  $content
	 * @return \Illuminate\Http\Response
	 */
	public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
	{
		$request = Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);

		return $this->response = $this->app->make('Illuminate\Contracts\Http\Kernel')->handle($request);
	}

	/**
	 * Call the given HTTPS URI and return the Response.
	 *
	 * @param  string  $method
	 * @param  string  $uri
	 * @param  array   $parameters
	 * @param  array   $cookies
	 * @param  array   $files
	 * @param  array   $server
	 * @param  string  $content
	 * @return \Illuminate\Http\Response
	 */
	public function callSecure($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
	{
		$uri = 'https://localhost/'.ltrim($uri, '/');

		return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
	}

	/**
	 * Call a controller action and return the Response.
	 *
	 * @param  string  $method
	 * @param  string  $action
	 * @param  array   $wildcards
	 * @param  array   $parameters
	 * @param  array   $cookies
	 * @param  array   $files
	 * @param  array   $server
	 * @param  string  $content
	 * @return \Illuminate\Http\Response
	 */
	public function action($method, $action, $wildcards = [], $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
	{
		$uri = $this->app['url']->action($action, $wildcards, true);

		return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
	}

	/**
	 * Call a named route and return the Response.
	 *
	 * @param  string  $method
	 * @param  string  $name
	 * @param  array   $routeParameters
	 * @param  array   $parameters
	 * @param  array   $cookies
	 * @param  array   $files
	 * @param  array   $server
	 * @param  string  $content
	 * @return \Illuminate\Http\Response
	 */
	public function route($method, $name, $routeParameters = [], $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
	{
		$uri = $this->app['url']->route($name, $routeParameters);

		return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
	}

	/**
	 * Set the session to the given array.
	 *
	 * @param  array  $data
	 * @return void
	 */
	public function session(array $data)
	{
		$this->startSession();

		foreach ($data as $key => $value)
		{
			$this->app['session']->put($key, $value);
		}
	}

	/**
	 * Flush all of the current session data.
	 *
	 * @return void
	 */
	public function flushSession()
	{
		$this->startSession();

		$this->app['session']->flush();
	}

	/**
	 * Start the session for the application.
	 *
	 * @return void
	 */
	protected function startSession()
	{
		if ( ! $this->app['session']->isStarted())
		{
			$this->app['session']->start();
		}
	}

	/**
	 * Set the currently logged in user for the application.
	 *
	 * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
	 * @param  string  $driver
	 * @return void
	 */
	public function be(UserContract $user, $driver = null)
	{
		$this->app['auth']->driver($driver)->setUser($user);
	}

	/**
	 * Seed a given database connection.
	 *
	 * @param  string  $class
	 * @return void
	 */
	public function seed($class = 'DatabaseSeeder')
	{
		$this->artisan('db:seed', ['--class' => $class]);
	}

	/**
	 * Call artisan command and return code.
	 *
	 * @param string  $command
	 * @param array   $parameters
	 * @return int
	 */
	public function artisan($command, $parameters = [])
	{
		return $this->code = $this->app['Illuminate\Contracts\Console\Kernel']->call($command, $parameters);
	}

}
