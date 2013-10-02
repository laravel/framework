<?php namespace Illuminate\Remote;

use Net_SFTP;
use Crypt_RSA;
use Illuminate\Filesystem\Filesystem;

class SecLibGateway implements GatewayInterface {

	/**
	 * The host name of the server.
	 *
	 * @var string
	 */
	protected $host;

	/**
	 * The SSH port on the server.
	 *
	 * @var int
	 */
	protected $port = 22;

	/**
	 * The authentication credential set.
	 *
	 * @var array
	 */
	protected $auth;

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new gateway implementation.
	 *
	 * @param  string  $host
	 * @param  array   $auth
	 * @return void
	 */
	public function __construct($host, array $auth, Filesystem $files)
	{
		$this->auth = $auth;
		$this->files = $files;
		$this->setHostAndPort($host);

		$this->connection = new Net_SFTP($this->host, $this->port);
	}

	/**
	 * Set the host and port from a full host string.
	 *
	 * @param  string  $host
	 * @return void
	 */
	protected function setHostAndPort($host)
	{
		if ( ! str_contains($host, ':'))
		{
			$this->host = $host;
		}
		else
		{
			list($this->host, $this->post) = explode($host, ':');

			$this->port = (int) $this->port;
		}
	}

	/**
	 * Connect to the SSH server.
	 *
	 * @param  string  $username
	 * @return void
	 */
	public function connect($username)
	{
		$this->connection->login($username, $this->getAuthForLogin());
	}

	/**
	 * Determine if the gateway is connected.
	 *
	 * @return bool
	 */
	public function connected()
	{
		return $this->connection->isConnected();
	}

	/**
	 * Run a command against the server (non-blocking).
	 *
	 * @param  string  $command
	 * @return void
	 */
	public function run($command)
	{
		$this->connection->exec($command, false);
	}

	/**
	 * Upload a local file to the server.
	 *
	 * @param  string  $local
	 * @param  string  $remote
	 * @return void
	 */
	public function put($local, $remote)
	{
		$this->connection->put($remote, $local, NET_SFTP_LOCAL_FILE);
	}

	/**
	 * Upload a string to to the given file on the server.
	 *
	 * @param  string  $remote
	 * @param  string  $contents
	 * @return void
	 */
	public function putString($remote, $contents)
	{
		$this->connection->put($remote, $contents);
	}

	/**
	 * Get the next line of output from the server.
	 *
	 * @return string|null
	 */
	public function nextLine()
	{
		$value = $this->connection->_get_channel_packet(NET_SSH2_CHANNEL_EXEC);

		return $value === true ? null : $value;
	}

	/**
	 * Get the authentication object for login.
	 *
	 * @return \Crypt_RSA|string
	 */
	protected function getAuthForLogin()
	{
		// If a "key" was specified in the auth credentials, we will load it into a
		// secure RSA key instance, which will be used to connect to the servers
		// in place of a password, and avoids the developer specifying a pass.
		if ($this->hasRsaKey())
		{
			return $this->loadRsaKey($this->auth['key']);
		}

		// If a plain password was set on the auth credentials, we will just return
		// that as it can be used to connect to the server. This will be used if
		// there is no RSA key and it gets specified in the credential arrays.
		elseif (isset($this->auth['password']))
		{
			return $this->auth['password'];
		}

		throw new \InvalidArgumentException('Password / key is required.');
	}

	/**
	 * Determine if an RSA key is configured.
	 *
	 * @return bool
	 */
	protected function hasRsaKey()
	{
		return (isset($this->auth['key']) and trim($this->auth['key']) != '');
	}

	/**
	 * Load the RSA key instance.
	 *
	 * @param  string  $path
	 * @return \Crypt_RSA
	 */
	protected function loadRsaKey($path)
	{
		with($key = new Crypt_RSA)->loadKey($this->files->get($path));

		return $key;
	}

	/**
	 * Get the exit status of the last command.
	 *
	 * @return int|bool
	 */
	public function status()
	{
		return $this->connection->getExitStatus();
	}

}