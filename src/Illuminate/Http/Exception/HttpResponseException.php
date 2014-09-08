<?php namespace Illuminate\Http\Exception;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class HttpResponseException extends RuntimeException {

	/**
	 * The underlying Response instance.
	 *
	 * @var \Symfony\Component\HttpFoundation\Response  $response
	 */
	protected $response;

	/**
	 * Create a new HTTP response exception instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function __construct(Response $response)
	{
		$this->response = $response;
	}

	/**
	 * Get the underlying response instance.
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getResponse()
	{
		return $this->response;
	}

}
