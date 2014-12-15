<?php namespace Illuminate\Database\Connectors;

use PDO;

class SqlServerConnector extends Connector implements ConnectorInterface {

	/**
	 * The PDO connection options.
	 *
	 * @var array
	 */
	protected $options = array(
		PDO::ATTR_CASE => PDO::CASE_NATURAL,
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
		PDO::ATTR_STRINGIFY_FETCHES => false,
	);

	/**
	 * Establish a database connection.
	 *
	 * @param  array  $config
	 * @return \PDO
	 */
	public function connect(array $config)
	{
		$options = $this->getOptions($config);

		return $this->createConnection($this->getDsn($config), $config, $options);
	}

	/**
	 * Create a DSN string from a configuration.
	 *
	 * @param  array   $config
	 * @return string
	 */
	protected function getDsn(array $config)
	{
		// First we will create the basic DSN setup as well as the port if it is in
		// in the configuration options. This will give us the basic DSN we will
		// need to establish the PDO connections and return them back for use.
		if (in_array('dblib', $this->getAvailableDrivers()))
		{
			return $this->getDblibDsn($config);
		}
		else
		{
			return $this->getSqlSrvDsn($config);
		}
	}

	/**
	 * Get the DSN string for a DbLib connection.
	 *
	 * @param  array  $config
	 * @return string
	 */
	protected function getDblibDsn(array $config)
	{
		$port = isset($config['port']) ? ':'.$config['port'] : '';

		return "dblib:host={$config['host']}{$port};dbname={$config['database']}";
	}

	/**
	 * Get the DSN string for a SqlSrv connection.
	 *
	 * @param  array  $config
	 * @return string
	 */
	protected function getSqlSrvDsn(array $config)
	{
		$port = isset($config['port']) ? ','.$config['port'] : '';

		$dbName = $config['database'] != '' ? ";Database={$config['database']}" : '';

		return "sqlsrv:Server={$config['host']}{$port}{$dbName}";
	}

	/**
	 * Get the available PDO drivers.
	 *
	 * @return array
	 */
	protected function getAvailableDrivers()
	{
		return PDO::getAvailableDrivers();
	}

}
