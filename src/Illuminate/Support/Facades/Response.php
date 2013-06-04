<?php namespace Illuminate\Support\Facades;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Contracts\ArrayableInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Response {

	/**
	 * Return a new response from the application.
	 *
	 * @param  string  $content
	 * @param  int     $status
	 * @param  array   $headers
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public static function make($content = '', $status = 200, array $headers = array())
	{
		return new \Illuminate\Http\Response($content, $status, $headers);
	}

	/**
	 * Return a new view response from the application.
	 *
	 * @param  string  $view
	 * @param  array   $data
	 * @param  int     $status
	 * @param  array   $headers
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public static function view($view, $data = array(), $status = 200, array $headers = array())
	{
		$app = Facade::getFacadeApplication();

		return static::make($app['view']->make($view, $data), $status, $headers);
	}

	/**
	 * Return a new JSON response from the application.
	 *
	 * @param  string|array  $data
	 * @param  int    $status
	 * @param  array  $headers
	 * @return \Illuminate\Http\JsonResponse
	 */
	public static function json($data = array(), $status = 200, array $headers = array())
	{
		if ($data instanceof ArrayableInterface)
		{
			$data = $data->toArray();
		}

		return new JsonResponse($data, $status, $headers);
	}

	/**
	 * Return a new streamed response from the application.
	 *
	 * @param  Closure  $callback
	 * @param  int      $status
	 * @param  array    $headers
	 * @return \Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public static function stream($callback, $status = 200, array $headers = array())
	{
		return new \Symfony\Component\HttpFoundation\StreamedResponse($callback, $status, $headers);
	}

	/**
	 * Create a new file download response.
	 *
	 * @param  SplFileInfo|string  $file
	 * @param  string  $name
	 * @param  array   $headers
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public static function download($file, $name = null, array $headers = array())
	{
		$response = new BinaryFileResponse($file, 200, $headers, true, 'attachment');

		if ( ! is_null($name))
		{
			return $response->setContentDisposition('attachment', $name);
		}

		return $response;
	}

}