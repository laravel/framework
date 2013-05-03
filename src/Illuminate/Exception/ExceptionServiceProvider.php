<?php namespace Illuminate\Exception;

use Closure;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Debug\Exception\FatalErrorException;

class ExceptionServiceProvider extends ServiceProvider {

	/**
	 * Start the error handling facilities.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function startHandling($app)
	{
		$this->setExceptionHandler($app['exception.function']);

		$this->registerErrorHandler();

		if (isset($app['env']) and $app['env'] != 'testing')
		{
			$this->registerShutdownHandler();
		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerKernelHandlers();

		$this->app['exception'] = $this->app->share(function()
		{
			return new Handler;
		});

		$this->registerExceptionHandler();

		$this->registerWhoops();
	}

	/**
	 * Register the HttpKernel error and exception handlers.
	 *
	 * @return void
	 */
	protected function registerKernelHandlers()
	{
		$app = $this->app;

		$app['kernel.exception'] =  $app->share(function() use ($app)
		{
			return new ExceptionHandler($app['config']['app.debug']);
		});
	}

	/**
	 * Register the PHP exception handler function.
	 *
	 * @return void
	 */
	protected function registerExceptionHandler()
	{
		list($me, $app) = array($this, $this->app);

		$app['exception.function'] = function() use ($me, $app)
		{
			return function($exception) use ($me, $app)
			{
				$response = $app['exception']->handle($exception);

				// If one of the custom error handlers returned a response, we will send that
				// response back to the client after preparing it. This allows a specific
				// type of exceptions to handled by a Closure giving great flexibility.
				if ( ! is_null($response))
				{
					$response = $app->prepareResponse($response, $app['request']);

					$response->send();
				}

				// If none of the custom handlers returned a response we will display default
				// error display for the application, which will either be the Whoops view
				// or the plain Symfony error page that does not contain errors details.
				else
				{
					$me->displayException($exception);
				}
			};
		};
	}

	/**
	 * Register the error handler.
	 *
	 * @return void
	 */
	public function registerErrorHandler()
	{
		list($me, $app) = array($this, $this->app);

		set_error_handler(function($level, $message, $file, $line, $context) use ($me, $app)
		{
			$app['exception.function'](new \ErrorException($message, $level, 0, $file, $line));
		});
	}

	/**
	 * Register the shutdown handler Closure.
	 *
	 * @return void
	 */
	protected function registerShutdownHandler()
	{
		$app = $this->app;

		register_shutdown_function(function() use ($app)
		{
			if ($e = error_get_last())
			{
				$app['exception.function'](new FatalErrorException($e['message'], $e['type'], 0, $e['file'], $e['line']));
			}
		});
	}

	/**
	 * Register the Whoops error display service.
	 *
	 * @return void
	 */
	protected function registerWhoops()
	{
		$this->registerWhoopsHandler();

		$this->app['whoops'] = $this->app->share(function($app)
		{
			$whoops = new \Whoops\Run;

			// We need to disable the Whoops outputting. Otherwise Whoops will try to write
			// stuff out to the screen. By doing this, we'll be able to set the response
			// status code since Whoops would force us to return out 200 status codes.
			$whoops->writeToOutput(false);

			$whoops->allowQuit(false);

			return $whoops->pushHandler($app['whoops.handler']);
		});
	}

	/**
	 * Register the Whoops handler for the request.
	 *
	 * @return void
	 */
	protected function registerWhoopsHandler()
	{
		if ($this->app['request']->ajax() or $this->app->runningInConsole())
		{
			$this->app['whoops.handler'] = function() { return new JsonResponseHandler; };
		}
		else
		{
			$this->registerPrettyWhoopsHandler();
		}
	}

	/**
	 * Register the "pretty" Whoops handler.
	 *
	 * @return void
	 */
	protected function registerPrettyWhoopsHandler()
	{
		$me = $this;
		
		$this->app['whoops.handler'] = function() use ($me)
		{
			with($handler = new PrettyPageHandler)->setEditor('sublime');

			if ( ! is_null($path = $me->resourcePath())) $handler->setResourcesPath($path);

			return $handler;
		};
	}

	/**
	 * Get the resource path for Whoops.
	 *
	 * @return string
	 */
	public function resourcePath()
	{
		if (is_dir($path = $this->app['path.base'].'/vendor/laravel/framework/src/Illuminate/Exception/resources'))
		{
			return $path;
		}
	}

	/**
	 * Display the given exception.
	 *
	 * @param  \Exception  $exception
	 * @return void
	 */
	public function displayException($exception)
	{
		if (isset($this->app['whoops']) and $this->app['config']['app.debug'])
		{
			$this->displayWhoopsException($exception);
		}
		else
		{
			$this->app['kernel.exception']->handle($exception);
		}
	}

	/**
	 * Display a exception using the Whoops library.
	 *
	 * @param  \Exception  $exception
	 * @return void
	 */
	protected function displayWhoopsException($exception)
	{
		$response = $this->app['whoops']->handleException($exception);

		with(new Response($response, 500))->send();
	}

	/**
	 * Set the given Closure as the exception handler.
	 *
	 * This function is mainly needed for mocking purposes.
	 *
	 * @param  Closure  $handler
	 * @return mixed
	 */
	protected function setExceptionHandler(Closure $handler)
	{
		return set_exception_handler($handler);
	}

}
