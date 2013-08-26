<?php namespace Illuminate\Foundation;

use Closure;
use Illuminate\Http\Request;

class EnvironmentDetector {

	/**
	 * The request instance.
	 *
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * Create a new environment detector instance.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Detect the application's current environment.
	 *
	 * @param  array|string  $environments
	 * @param  bool  $inConsole
	 * @return string
	 */
	public function detect($environments, $inConsole = false)
	{
		if ($inConsole)
		{
			return $this->detectConsoleEnvironment($environments);
		}
		else
		{
			return $this->detectWebEnvironment($environments);
		}
	}

	/**
	 * Set the application environment for a web request.
	 *
	 * @param  array|string  $environments
	 * @return string
	 */
	protected function detectWebEnvironment($environments)
	{
		// If the given environment is just a Closure, we will defer the environment check
		// to the Closure the developer has provided, which allows them to totally swap
		// the webs environment detection logic with their own custom Closure's code.
		if ($environments instanceof Closure)
		{
			return call_user_func($environments);
		}

		$webHost = $this->getHost();

		foreach ($environments as $environment => $hosts)
		{
			// To determine the current environment, we'll simply iterate through the possible
			// environments and look for the host that matches the host for this request we
			// are currently processing here, then return back these environment's names.
			foreach ((array) $hosts as $host)
			{
				if (str_is($host, $webHost) or $this->isMachine($host))
				{
					return $environment;
				}
			}
		}

		return 'production';
	}

	/**
	 * Set the application environment from command-line arguments.
	 *
	 * @param  mixed   $environments
	 * @return string
	 */
	protected function detectConsoleEnvironment($environments)
	{
		// First we will check if an environment argument was passed via console arguments
		// and if it was that automatically overrides as the environment. Otherwise, we
		// will check the environment as a "web" request like a typical HTTP request.
		if ( ! is_null($value = $this->getEnvironmentArgument()))
		{
			return head(array_slice(explode('=', $value), 1));
		}
		else
		{
			return $this->detectWebEnvironment($environments);
		}
	}

	/**
	 * Get the enviornment argument from the console.
	 *
	 * @return string|null
	 */
	protected function getEnvironmentArgument()
	{
		return array_first($this->getConsoleArguments(), function($k, $v)
		{
			return starts_with($v, '--env');
		});
	}

	/**
	 * Get the actual host for the web request.
	 *
	 * @return string
	 */
	protected function getHost()
	{
		return $this->request->getHost();
	}

	/**
	 * Get the server console arguments.
	 *
	 * @return array
	 */
	protected function getConsoleArguments()
	{
		return $this->request->server->get('argv');
	}

	/**
	 * Determine if the name matches the machine name.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	protected function isMachine($name)
	{
		return str_is($name, gethostname());
	}

}