<?php namespace Illuminate\Contracts\Http;

interface Kernel {

	/**
	 * Bootstrap the application for HTTP requests.
	 *
	 * @return void
	 */
	public function bootstrap();

	/**
	 * Handle an incoming HTTP request.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handle($request);

	/**
	 * Get the Laravel application instance.
	 *
	 * @return \Illuminate\Contracts\Foundation\Application
	 */
	public function getApplication();

}
