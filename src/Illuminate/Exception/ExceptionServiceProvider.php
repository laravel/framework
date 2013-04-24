<?php namespace Illuminate\Exception;

use Closure;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Debug\ErrorHandler;
use Symfony\Component\HttpKernel\Debug\ExceptionHandler as KernelHandler;

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

		// By registering the error handler with a level of -1, we state that we want
		// all PHP errors converted into ErrorExceptions and thrown which provides
		// a very strict development environment but prevents any unseen errors.
		$app['kernel.error']->register(-1);

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

		$app['kernel.error'] = function()
		{
			return new ErrorHandler;
		};

		$this->app['kernel.exception'] = function() use ($app)
		{
			return new KernelHandler($app['config']['app.debug']);
		};
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
				else
				{
					$me->displayException($exception);
				}
			};
		};
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
			set_exception_handler(array(new StubShutdownHandler($app), 'handle'));

			$app['kernel.error']->handleFatal();
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
		if ($this->app['config']['app.debug'])
		{
			return $this->displayWhoopsException($exception);
		}

		$this->app['kernel.exception']->handle($exception);
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
