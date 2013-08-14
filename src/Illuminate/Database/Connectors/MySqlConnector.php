<?php namespace Illuminate\Database\Connectors;

class MySqlConnector extends Connector implements ConnectorInterface {

	/**
	 * Establish a database connection.
	 *
	 * @param  array  $config
	 * @return PDO
	 */
	public function connect(array $config)
	{
		$dsn = $this->getDsn($config);

		// We need to grab the PDO options that should be used while making the brand
		// new connection instance. The PDO options control various aspects of the
		// connection's behavior, and some might be specified by the developers.
		$options = $this->getOptions($config);

		$connection = $this->createConnection($dsn, $config, $options);

		$collation = $config['collation'];

		$charset = $config['charset'];

		// Next we will set the "names" and "collation" on the clients connections so
		// a correct character set will be used by this client. The collation also
		// is set on the server but needs to be set here on this client objects.
		$names = "set names '$charset' collate '$collation'";

		$connection->prepare($names)->execute();

		return $connection;
	}

	/**
	 * Create a DSN string from a configuration.
	 *
	 * @param  array   $config
	 * @return string
	 */
	protected function getDsn(array $config)
	{
		extract($config);
 
		// Sometimes the developer may specify the specific UNIX socket that should be used
		if (isset($config['unix_socket']))
		{
			$dsn = "unix_socket={$config['unix_socket']}";
		}
		else
		{
			// But more often a single hostname is supplied with an optionnal port number.
			// Warning : when 'localhost' is used, the mysql driver will use the default 
			//			unix socket value, use '127.0.0.1' instead.
			// Note :	according to the PHP documentation the unix_socket option and the 
			//			host[port] option should not be used together.
			$dsn = "host={$host}";
 
			if (isset($config['port']))
			{
				$dsn .= ";port={$port}";
			}
		}
 
		// Finally, the 'mysql:' prefix as well as the database name are added
		$dsn = "mysql:{$dsn};dbname={$database}";
 
		return $dsn;
	}

}
