<?php namespace Illuminate\Foundation\Testing;

use Illuminate\View\View;
use PHPUnit_Framework_Assert as PHPUnit;

trait AssertionsTrait {

	/**
	 * Assert that the client response has an OK status code.
	 *
	 * @return void
	 */
	public function assertResponseOk()
	{
		$response = $this->client->getResponse();

		$actual = $response->getStatusCode();

		return PHPUnit::assertTrue($response->isOk(), 'Expected status code 200, got ' .$actual);
	}

	/**
	 * Assert that the client response has a given code.
	 *
	 * @param  int  $code
	 * @return void
	 */
	public function assertResponseStatus($code)
	{
		return PHPUnit::assertEquals($code, $this->client->getResponse()->getStatusCode());
	}

	/**
	 * Assert that the response view has a given piece of bound data.
	 *
	 * @param  string|array  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function assertViewHas($key, $value = null)
	{
		if (is_array($key)) return $this->assertViewHasAll($key);

		$response = $this->client->getResponse();

		if ( ! isset($response->original) || ! $response->original instanceof View)
		{
			return PHPUnit::assertTrue(false, 'The response was not a view.');
		}

		if (is_null($value))
		{
			PHPUnit::assertArrayHasKey($key, $response->original->getData());
		}
		else
		{
			PHPUnit::assertEquals($value, $response->original->$key);
		}
	}

	/**
	 * Assert that the view has a given list of bound data.
	 *
	 * @param  array  $bindings
	 * @return void
	 */
	public function assertViewHasAll(array $bindings)
	{
		foreach ($bindings as $key => $value)
		{
			if (is_int($key))
			{
				$this->assertViewHas($value);
			}
			else
			{
				$this->assertViewHas($key, $value);
			}
		}
	}

	/**
	 * Assert that the response view is missing a piece of bound data.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function assertViewMissing($key)
	{
		$response = $this->client->getResponse();

		if ( ! isset($response->original) || ! $response->original instanceof View)
		{
			return PHPUnit::assertTrue(false, 'The response was not a view.');
		}

		PHPUnit::assertArrayNotHasKey($key, $response->original->getData());
	}

	/**
	 * Assert whether the client was redirected to a given URI.
	 *
	 * @param  string  $uri
	 * @param  array   $with
	 * @return void
	 */
	public function assertRedirectedTo($uri, $with = array())
	{
		$response = $this->client->getResponse();

		PHPUnit::assertInstanceOf('Illuminate\Http\RedirectResponse', $response);

		PHPUnit::assertEquals($this->app['url']->to($uri), $response->headers->get('Location'));

		$this->assertSessionHasAll($with);
	}

	/**
	 * Assert whether the client was redirected to a given route.
	 *
	 * @param  string  $name
	 * @param  array   $parameters
	 * @param  array   $with
	 * @return void
	 */
	public function assertRedirectedToRoute($name, $parameters = array(), $with = array())
	{
		$this->assertRedirectedTo($this->app['url']->route($name, $parameters), $with);
	}

	/**
	 * Assert whether the client was redirected to a given action.
	 *
	 * @param  string  $name
	 * @param  array   $parameters
	 * @param  array   $with
	 * @return void
	 */
	public function assertRedirectedToAction($name, $parameters = array(), $with = array())
	{
		$this->assertRedirectedTo($this->app['url']->action($name, $parameters), $with);
	}

	/**
	 * Assert that the session has a given list of values.
	 *
	 * @param  string|array  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function assertSessionHas($key, $value = null)
	{
		if (is_array($key)) return $this->assertSessionHasAll($key);

		if (is_null($value))
		{
			PHPUnit::assertTrue($this->app['session.store']->has($key), "Session missing key: $key");
		}
		else
		{
			PHPUnit::assertEquals($value, $this->app['session.store']->get($key));
		}
	}

	/**
	 * Assert that the session has a given list of values.
	 *
	 * @param  array  $bindings
	 * @return void
	 */
	public function assertSessionHasAll(array $bindings)
	{
		foreach ($bindings as $key => $value)
		{
			if (is_int($key))
			{
				$this->assertSessionHas($value);
			}
			else
			{
				$this->assertSessionHas($key, $value);
			}
		}
	}

	/**
	 * Assert that the session has errors bound.
	 *
	 * @param  string|array  $bindings
	 * @param  mixed  $format
	 * @return void
	 */
	public function assertSessionHasErrors($bindings = array(), $format = null)
	{
		$this->assertSessionHas('errors');

		$bindings = (array) $bindings;

		$errors = $this->app['session.store']->get('errors');

		foreach ($bindings as $key => $value)
		{
			if (is_int($key))
			{
				PHPUnit::assertTrue($errors->has($value), "Session missing error: $value");
			}
			else
			{
				PHPUnit::assertContains($value, $errors->get($key, $format));
			}
		}
	}

	/**
	 * Assert that the session has old input.
	 *
	 * @return void
	 */
	public function assertHasOldInput()
	{
		$this->assertSessionHas('_old_input');
	}

}
