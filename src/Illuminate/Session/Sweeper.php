<?php namespace Illuminate\Session;

interface Sweeper {

	/**
	 * Remove session records older than a given expiration.
	 *
	 * @param  int   $expiration
	 * @return void
	 */
	public function sweep($expiration);

}