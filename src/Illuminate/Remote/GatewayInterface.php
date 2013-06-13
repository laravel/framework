<?php namespace Illuminate\Remote;

interface GatewayInterface {

	/**
	 * Connect to the SSH server.
	 *
	 * @param  string  $username
	 * @return void
	 */
	public function connect($username);

	/**
	 * Determine if the gateway is connected.
	 *
	 * @return bool
	 */
	public function connected();

	/**
	 * Run a command against the server (non-blocking).
	 *
	 * @param  string  $command
	 * @return void
	 */
	public function run($comamnd);

	/**
	 * Get the next line of output from the server.
	 *
	 * @return string|null
	 */
	public function nextLine();

}