<?php namespace Illuminate\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class PlainDisplayer implements ExceptionDisplayerInterface {

	/**
	 * Display the given exception to the user.
	 *
	 * @param  \Exception  $exception
	 */
	public function display(Exception $exception)
	{
		return new Response(file_get_contents(__DIR__.'/resources/plain.html'), 500);
	}

}