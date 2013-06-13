<?php namespace Illuminate\Remote;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Connection implements ConnectionInterface {

	/**
	 * The SSH gateway implementation.
	 *
	 * @var \Illuminate\Remote\GatewayInterface
	 */
	protected $gateway;

	/**
	 * The host name of the server.
	 *
	 * @var string
	 */
	protected $host;

	/**
	 * The username for the connection.
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * The authentication credential set.
	 *
	 * @var array
	 */
	protected $auth;

	/**
	 * The output implementation for the connection.
	 *
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected $output;

	/**
	 * Create a new SSH connection instance.
	 *
	 * @param  string  $host
	 * @param  string  $username
	 * @param  array   $auth
	 * @param  \Illuminate\Remote\GatewayInterface
	 * @param  
	 */
	public function __construct($host, $username, array $auth, GatewayInterface $gateway = null)
	{
		$this->host = $host;
		$this->username = $username;
		$this->gateway = $gateway ?: new SecLibGateway($host, $auth, new Filesystem);
	}

	/**
	 * Run a set of commands against the connection.
	 *
	 * @param  string|array  $commands
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function run($commands, Closure $callback = null)
	{
		// First, we will intitilize the SSH gateway, and then format the commands so
		// they can be run. Once we have the commands formatted and the server is
		// ready to go we will just fire off these commands against the server.
		$gateway = $this->getGateway();

		$callback = $this->getCallback($callback);

		$gateway->run($this->formatCommands($commands));

		// After running the commands against the server, we will continue to ask for
		// the next line of output that is available, and write it them out using
		// our callback. Once we hit the end of output, we'll bail out of here.
		while (true)
		{
			if (is_null($line = $gateway->nextLine())) break;

			call_user_func($callback, $line, $this);
		}
	}

	/**
	 * Display the given line using the default output.
	 *
	 * @param  string  $line
	 * @return void
	 */
	public function display($line)
	{
		$lead = '<comment>['.$this->username.'@'.$this->host.']</comment>';

		$this->getOutput()->writeln($lead.' '.$line);
	}

	/**
	 * Format the given command set.
	 *
	 * @param  string|array  $commands
	 * @return string
	 */
	protected function formatCommands($commands)
	{
		return is_array($commands) ? implode(' && ', $commands) : $commands;
	}

	/**
	 * Get the display callback for the connection.
	 *
	 * @param  \Closure|null  $callback
	 * @return \Closure
	 */
	protected function getCallback($callback)
	{
		if ( ! is_null($callback)) return $callback;

		$me = $this;

		return function($line) use ($me) { $me->display($line); };
	}

	/**
	 * Get the gateway implementation.
	 *
	 * @return \Illuminate\Remote\GatewayInterface
	 */
	public function getGateway()
	{
		if ( ! $this->gateway->connected())
		{
			$this->gateway->connect($this->username);
		}

		return $this->gateway;
	}

	/**
	 * Get the output implementation for the connection.
	 *
	 * @return \Symfony\Component\Console\Output\OutputInterface
	 */
	public function getOutput()
	{
		if (is_null($this->output)) $this->output = new NullOutput;

		return $this->output;
	}

	/**
	 * Set the output implementation.
	 *
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return void
	 */
	public function setOutput(OutputInterface $output)
	{
		$this->output = $output;
	}

}