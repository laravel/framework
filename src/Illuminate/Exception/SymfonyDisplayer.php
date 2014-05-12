<?php namespace Illuminate\Exception;

use Exception;
use Symfony\Component\Debug\ExceptionHandler;

class SymfonyDisplayer implements ExceptionDisplayerInterface {

	/**
	 * The Symfony exception handler.
	 *
	 * @var \Symfony\Component\Debug\ExceptionHandler
	 */
	protected $symfony;

	/**
	 * Create a new Symfony exception displayer.
	 *
	 * @param  \Symfony\Component\Debug\ExceptionHandler  $symfony
	 * @return void
	 */
	public function __construct(ExceptionHandler $symfony)
	{
		$this->symfony = $symfony;
	}

	/**
	 * Display the given exception to the user.
	 *
	 * @param  \Exception  $exception
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function display(Exception $exception)
	{
		return $this->symfony->createResponse($exception);
	}

}
