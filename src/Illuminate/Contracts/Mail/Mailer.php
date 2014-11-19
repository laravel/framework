<?php namespace Illuminate\Contracts\Mail;

interface Mailer {

	/**
	 * Send a new message when only a raw text part.
	 *
	 * @param  string  $text
	 * @param  \Closure|string  $callback
	 * @return int
	 */
	public function raw($text, $callback);

	/**
	 * Send a new message using a view.
	 *
	 * @param  string|array  $view
	 * @param  array  $data
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	public function send($view, array $data, $callback);

	/**
	 * Get the array of failed recipients.
	 *
	 * @return array
	 */
	public function failures();

}
