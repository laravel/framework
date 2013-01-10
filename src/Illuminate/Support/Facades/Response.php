<?php namespace Illuminate\Support\Facades;

class Response {

	/**
	 * Return a new response from the application.
	 *
	 * @param  string  $content
	 * @param  int     $status
	 * @param  array   $headers
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public static function make($content = '', $status = 200, array $headers = array())
	{
		return new \Illuminate\Http\Response($content, $status, $headers);
	}

	/**
	 * Return a new JSON response from the application.
	 *
	 * @param  string  $content
	 * @param  int     $status
	 * @param  array   $headers
	 * @return Symfony\Component\HttpFoundation\JsonResponse
	 */
	public static function json($data = array(), $status = 200, array $headers = array())
	{
		return new \Symfony\Component\HttpFoundation\JsonResponse($data, $status, $headers);
	}

	/**
	 * Return a new streamed response from the application.
	 *
	 * @param  Closure  $callback
	 * @param  int      $status
	 * @param  array    $headers
	 * @return Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public static function stream($callback, $status = 200, array $headers = array())
	{
		return new \Symfony\Component\HttpFoundation\StreamedResponse($callback, $status, $headers);
	}

	/**
	 * Create a new file download response.
	 *
	 * @param  SplFileInfo|string  $file
	 * @param  int  $status
	 * @param  array  $headers
	 * @return Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public static function download($file, $status = 200, $headers = array())
	{
		return new \Symfony\Component\HttpFoundation\BinaryFileResponse($file, $status, $headers);
	}

}