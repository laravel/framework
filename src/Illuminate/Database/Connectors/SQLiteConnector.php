<?php namespace Illuminate\Database\Connectors;

class SQLiteConnector extends Connector implements ConnectorInterface {

	/**
	 * Establish a database connection.
	 *
	 * @param  array  $options
	 * @return PDO
	 */
	public function connect(array $config)
	{
		$options = $this->getOptions($config);

		// SQLite supports "in-memory" databases that only last as long as the owning
		// connection does. These are useful for tests or for short lifetime store
		// querying. In-memory databases may only have a single open connection.
		if ($config['database'] == ':memory:')
		{
			return $this->createConnection('sqlite::memory:', $config, $options);
		}

		$path = realpath($config['database']);

		return $this->createConnection("sqlite:{$path}", $config, $options);
	}

}