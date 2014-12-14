<?php namespace Illuminate\Foundation\Bootstrap;

use ErrorException;
use Symfony\Component\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Debug\Exception\FatalErrorException;

class HandleExceptions {

	/**
	 * The application instance.
	 *
	 * @var \Illuminate\Contracts\Foundation\Application
	 */
	protected $app;

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$this->app = $app;

		error_reporting(-1);

		set_error_handler([$this, 'handleError']);

		set_exception_handler([$this, 'handleException']);

		register_shutdown_function([$this, 'handleShutdown']);

		if ( ! $app->environment('testing'))
		{
			ini_set('display_errors', 'Off');
		}
	}

	/**
	 * Convert a PHP error to an ErrorException.
	 *
	 * @param  int  $level
	 * @param  string  $message
	 * @param  string  $file
	 * @param  int  $line
	 * @param  array  $context
	 * @return void
	 */
	public function handleError($level, $message, $file = '', $line = 0, $context = array())
	{
		if (error_reporting() & $level)
		{
			throw new ErrorException($message, 0, $level, $file, $line);
		}
	}

	/**
	 * Handle an uncaught exception from the application.
	 *
	 * Note: Most exceptions can be handled via the try / catch block in
	 * the HTTP and Console kernels. But, fatal error exceptions must
	 * be handled differently since they are not normal exceptions.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	public function handleException($e)
	{
		$this->getExceptionHandler()->report($e);

		if ($this->app->runningInConsole())
		{
			$this->renderForConsole($e);
		}
		else
		{
			$this->renderHttpResponse($e);
		}
	}

	/**
	 * Render an exception to the console.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	protected function renderForConsole($e)
	{
		$this->getExceptionHandler()->renderForConsole(new ConsoleOutput, $e);
	}

	/**
	 * Render an exception as an HTTP response and send it.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	protected function renderHttpResponse($e)
	{
		$this->getExceptionHandler()->render($this->app['request'], $e)->send();
	}

	/**
	 * Handle the PHP shutdown event.
	 *
	 * @return void
	 */
	public function handleShutdown()
	{
		if ( ! is_null($error = error_get_last()) && $this->isFatal($error['type']))
		{
			$this->handleException($this->fatalExceptionFromError($error));
		}
	}

	/**
	 * Create a new fatal exception instance from an error array.
	 *
	 * @param  array  $error
	 * @return \Symfony\Component\Debug\Exception\FatalErrorException
	 */
	protected function fatalExceptionFromError(array $error)
	{
		return new FatalErrorException(
			$error['message'], $error['type'], 0, $error['file'], $error['line']
		);
	}

	/**
	 * Determine if the error type is fatal.
	 *
	 * @param  int  $type
	 * @return bool
	 */
	protected function isFatal($type)
	{
		return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
	}

	/**
	 * Get an instance of the exception handler.
	 *
	 * @return \Illuminate\Contracts\Debug\ExceptionHandler
	 */
	protected function getExceptionHandler()
	{
		return $this->app->make('Illuminate\Contracts\Debug\ExceptionHandler');
	}

}
