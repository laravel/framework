<?php namespace Illuminate\Redis;

class Database {

	/**
	 * The host address of the database.
	 *
	 * @var string
	 */
	protected $host;

	/**
	 * The port of the database.
	 *
	 * @var int
	 */
	protected $port;

	/**
	 * The database number to be selected.
	 *
	 * @var int
	 */
	protected $database;

	/**
	 * The password value.
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * The Redis connection handler.
	 *
	 * @var resource
	 */
	protected $connection;

	/**
	 * Create a new Redis connection instance.
	 *
	 * @param  string  $host
	 * @param  int     $port
	 * @param  int     $database
	 * @param  string  $password
	 * @return void
	 */
	public function __construct($host, $port, $database = 0, $password = null)
	{
		$this->host = $host;
		$this->port = $port;
		$this->database = $database;
		$this->password = $password;
	}

	/**
	 * Connect to the Redis database.
	 *
	 * @return void
	 */
	public function connect()
	{
		if ( ! is_null($this->connection)) return;

		$this->connection = $this->openSocket();

		if ( ! is_null($this->password))
		{
			$this->command('auth', array($this->password));
		}

		$this->command('select', array($this->database));
	}

	/**
	 * Run a command against the Redis database.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function command($method, array $parameters = array())
	{
		$this->fileWrite($this->buildCommand($method, $parameters));

		return $this->parseResponse($this->fileGet(512));
	}

	/**
	 * Build the Redis command syntax.
	 *
	 * Redis protocol states that a command should conform to the following format:
	 *
	 *     *<number of arguments> CR LF
	 *     $<number of bytes of argument 1> CR LF
	 *     <argument data> CR LF
	 *     ...
	 *     $<number of bytes of argument N> CR LF
	 *     <argument data> CR LF
	 *
	 * More information regarding the Redis protocol: http://redis.io/topics/protocol
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return string
	 */
	public function buildCommand($method, array $parameters)
	{
		$command = '*'.(count($parameters) + 1)."\r\n";

		// Before each parameter, we must send the number of bytes in the upcoming
		// value that we are sending. So, we'll just take the sting length of a
		// parameter and add it to the command. Then we'll add the parameter.
		$command .= '$'.strlen($method)."\r\n";

		$command .= strtoupper($method)."\r\n";

		foreach ($parameters as $parameter)
		{
			$command .= '$'.strlen($parameter)."\r\n".$parameter."\r\n";
		}

		return $command;
	}

	/**
	 * Parse the Redis database response.
	 *
	 * @param  string  $response
	 * @return mixed
	 */
	public function parseResponse($response)
	{
		switch (substr($response, 0, 1))
		{
			// The first character of the response tells us what type of response we
			// are dealing with. So we will process the response according to the
			// the type of response we've received back from the Redis command.
			case '+':
				return $this->parseInlineResponse($response);

			case ':':
				return (int) $this->parseInlineResponse($response);

			case '$':
				return $this->parseBulkResponse($response);

			// "Multi-bulk" responses are used to handle responses that contain many
			// values such as the response for a "lrange" command to the database
			// and these will be handled similarly to regular "bulk" responses.
			case '*':
				return $this->parseMultiResponse($response);
		}

		throw new CommandException(trim($response));
	}

	/**
	 * Parse an inline response from the database.
	 *
	 * @param  string  $response
	 * @return string
	 */
	protected function parseInlineResponse($response)
	{
		return substr(trim($response), 1);
	}

	/**
	 * Parse a bulk response from the database.
	 *
	 * @param  string  $response
	 * @return string
	 */
	protected function parseBulkResponse($response)
	{
		// If the response size is listed as a negative one it means that there is
		// no data for the given keys, and we should return "null" according to
		// the Redis protocol documentation for every client Redis libraries.
		if (trim($response) == '$-1')
		{
			return null;
		}

		$total = substr($response, 1);

		list($read, $data) = array(0, '');

		// If we have bytes to read, we will read off the data in 1024 byte chunks
		// and then return the response. Once we've read the data, we will also
		// need to read the final two byte new line feed off the file stream.
		if ($total > 0)
		{
			do
			{
				$data .= $this->readBulkBlock($total, $read);

			} while ($read < $total);
		}

		// After every response there is a final two byte new line feed that we'll
		// need to read off this stream to get it out of the way for subsequent
		// command responses that we may retrieve from the Redis connections.
		$this->fileRead(2);

		return $data;
	}

	/**
	 * Read the next block of bytes for a bulk response.
	 *
	 * @param  int     $total
	 * @param  int     $read
	 * @return string
	 */
	protected function readBulkBlock($total, &$read)
	{
		$block = (($remaining = $total - $read) < 1024) ? $remaining : 1024;
		
		$read += $block;

		return $this->fileRead($block);
	}

	/**
	 * Parse a multi-bulk response from the database.
	 *
	 * @param  string  $response
	 * @return array
	 */
	protected function parseMultiResponse($response)
	{
		if (($total = substr($response, 1)) == '-1') return;

		$data = array();

		// When reading off multi-bulk responses, we can just send the response back
		// through the typical parse routines since a multi-bulk is simply a list
		// of plain bulk responses. We'll just iterate over the response count.
		for ($i = 0; $i < $total; $i++)
		{
			$data[] = $this->parseResponse($this->fileGet(512));
		}

		return $data;
	}

	/**
	 * Get the socket connection to the database.
	 *
	 * @return resource
	 */
	protected function openSocket()
	{
		$connection = @fsockopen($this->host, $this->port, $error, $message);

		if ($connection === false)
		{
			throw new ConnectionException("{$error} - {$message}");
		}

		return $connection;
	}

	/**
	 * Read the specified number of bytes from the file resource.
	 *
	 * @param  int     $bytes
	 * @return string
	 */
	public function fileRead($bytes)
	{
		return fread($this->getConnection(), $bytes);
	}

	/**
	 * Get the specified number of bytes from a file line.
	 *
	 * @param  int     $bytes
	 * @return string
	 */
	public function fileGet($bytes)
	{
		return fgets($this->getConnection(), $bytes);
	}

	/**
	 * Write the given command to the file resource.
	 *
	 * @param  string  $command
	 * @return void
	 */
	public function fileWrite($command)
	{
		fwrite($this->getConnection(), $command);
	}

	/**
	 * Get the Redis socket connection.
	 *
	 * @return resource
	 */
	public function getConnection()
	{
		$this->connect();

		return $this->connection;
	}

	/**
	 * Dynamically make a Redis command.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->command($method, $parameters);
	}

}