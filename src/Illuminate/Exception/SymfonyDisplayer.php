<?php namespace Illuminate\Exception;

use Exception;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\JsonResponse;

class SymfonyDisplayer implements ExceptionDisplayerInterface {

	/**
	 * The Symfony exception handler.
	 *
	 * @var \Symfony\Component\Debug\ExceptionHandler
	 */
	protected $symfony;

	/**
	 * Indicates if JSON should be returned.
	 *
	 * @var bool
	 */
	protected $returnJson;

	/**
	 * Create a new Symfony exception displayer.
	 *
	 * @param  \Symfony\Component\Debug\ExceptionHandler  $symfony
	 * @param  bool  $returnJson
	 * @return void
	 */
	public function __construct(ExceptionHandler $symfony, $returnJson = false)
	{
		$this->symfony = $symfony;
		$this->returnJson = $returnJson;
	}

	/**
	 * Display the given exception to the user.
	 *
	 * @param  \Exception  $exception
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function display(Exception $exception)
	{
		if ($this->returnJson)
		{
			return new JsonResponse(array(
				'error' => $exception->getMessage(),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
			), 500);
		}

		return $this->symfony->createResponse($exception);
	}

}
