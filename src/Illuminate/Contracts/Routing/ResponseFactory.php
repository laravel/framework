<?php namespace Illuminate\Contracts\Routing;

interface ResponseFactory {

	/**
	 * Return a new response from the application.
	 *
	 * @param  string  $content
	 * @param  int     $status
	 * @param  array   $headers
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function make($content = '', $status = 200, array $headers = array());

	/**
	 * Return a new view response from the application.
	 *
	 * @param  string  $view
	 * @param  array   $data
	 * @param  int     $status
	 * @param  array   $headers
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function view($view, $data = array(), $status = 200, array $headers = array());

	/**
	 * Return a new JSON response from the application.
	 *
	 * @param  string|array  $data
	 * @param  int    $status
	 * @param  array  $headers
	 * @param  int    $options
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function json($data = array(), $status = 200, array $headers = array(), $options = 0);

	/**
	 * Return a new JSONP response from the application.
	 *
	 * @param  string  $callback
	 * @param  string|array  $data
	 * @param  int    $status
	 * @param  array  $headers
	 * @param  int    $options
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function jsonp($callback, $data = array(), $status = 200, array $headers = array(), $options = 0);

	/**
	 * Return a new streamed response from the application.
	 *
	 * @param  \Closure  $callback
	 * @param  int      $status
	 * @param  array    $headers
	 * @return \Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public function stream($callback, $status = 200, array $headers = array());

	/**
	 * Create a new file download response.
	 *
	 * @param  \SplFileInfo|string  $file
	 * @param  string  $name
	 * @param  array   $headers
	 * @param  null|string  $disposition
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public function download($file, $name = null, array $headers = array(), $disposition = 'attachment');

	/**
	 * Create a new redirect response to the given path.
	 *
	 * @param  string  $path
	 * @param  int     $status
	 * @param  array   $headers
	 * @param  bool    $secure
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function redirectTo($path, $status = 302, $headers = array(), $secure = null);

	/**
	 * Create a new redirect response to a named route.
	 *
	 * @param  string  $route
	 * @param  array   $parameters
	 * @param  int     $status
	 * @param  array   $headers
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function redirectToRoute($route, $parameters = array(), $status = 302, $headers = array());

	/**
	 * Create a new redirect response to a controller action.
	 *
	 * @param  string  $action
	 * @param  array   $parameters
	 * @param  int     $status
	 * @param  array   $headers
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function redirectToAction($action, $parameters = array(), $status = 302, $headers = array());

	/**
	 * Create a new redirect response, while putting the current URL in the session.
	 *
	 * @param  string  $path
	 * @param  int     $status
	 * @param  array   $headers
	 * @param  bool    $secure
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function redirectGuest($path, $status = 302, $headers = array(), $secure = null);

	/**
	 * Create a new redirect response to the previously intended location.
	 *
	 * @param  string  $default
	 * @param  int     $status
	 * @param  array   $headers
	 * @param  bool    $secure
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function redirectToIntended($default = '/', $status = 302, $headers = array(), $secure = null);

}
