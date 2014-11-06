<?php namespace Illuminate\Foundation\Debug;

use Exception;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Config\Repository as Configuration;
use Symfony\Component\Debug\ExceptionHandler as SymfonyDisplayer;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;

class ExceptionHandler implements ExceptionHandlerContract {

	/**
	 * The configuration repository implementation.
	 *
	 * @var \Illuminate\Contracts\Config\Repository
	 */
	protected $config;

	/**
	 * The log implementation.
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $log;

	/**
	 * Create a new exception handler instance.
	 *
	 * @param  \Illuminate\Contracts\Config\Repository  $config
	 * @param  \Psr\Log\LoggerInterface  $log
	 * @return void
	 */
	public function __construct(Configuration $config, LoggerInterface $log)
	{
		$this->log = $log;
		$this->config = $config;
	}

	/**
	 * Report or log an exception.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	public function report(Exception $e)
	{
		$this->log->error((string) $e);
	}

	/**
	 * Render an exception into a response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function render($request, Exception $e)
	{
		return (new SymfonyDisplayer($this->config->get('app.debug')))->createResponse($e);
	}

	/**
	 * Render an exception to the console.
	 *
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  \Exception  $e
	 * @return void
	 */
	public function renderForConsole($output, Exception $e)
	{
		$output->writeln((string) $e);
	}

}
