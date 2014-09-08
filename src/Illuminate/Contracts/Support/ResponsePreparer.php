<?php namespace Illuminate\Contracts\Support;

interface ResponsePreparer {

	/**
	 * Prepare the given value as a Response object.
	 *
	 * @param  mixed  $value
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function prepareResponse($value);

	/**
	 * Determine if provider is ready to return responses.
	 *
	 * @return bool
	 */
	public function readyForResponses();

}
