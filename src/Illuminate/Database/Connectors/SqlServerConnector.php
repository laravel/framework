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

		return $this->getSqlSrvDsn($config);
	}

	/**
	 * Get the DSN string for a DbLib connection.
	 *
	 * @param  array  $config
	 * @return string
	 */
	protected function getDblibDsn(array $config)
	{
		$arguments = array(
			'host' => $this->buildHostString($config, ':'),
			'dbname' => $config['database']
		);

		$arguments = $this->appendIfConfigKeyAvailable('appname', 'appname', $config, $arguments);
		$arguments = $this->appendIfConfigKeyAvailable('charset', 'charset', $config, $arguments);

		return $this->buildConnectString('dblib', $arguments);
	}

	/**
	 * Get the DSN string for a SqlSrv connection.
	 *
	 * @param  array  $config
	 * @return string
	 */
	protected function getSqlSrvDsn(array $config)
	{
		$arguments = array(
			'Server' => $this->buildHostString($config, ',')
		);

		$arguments = $this->appendIfConfigKeyAvailable('database', 'Database', $config, $arguments);
		$arguments = $this->appendIfConfigKeyAvailable('appname', 'APP', $config, $arguments);
		
		return $this->buildConnectString('sqlsrv', $arguments);
	}

	private function buildConnectString($driver, array $arguments)
	{
		$connectStringOptions = array_map(function($key) use ($arguments)
		{
			return sprintf("%s=%s", $key, $arguments[$key]);
		}, array_keys($arguments));

		return $driver.":".implode(';', $connectStringOptions);
	}

	private function buildHostString(array $config, $separator)
	{
		if(isset($config['port']))
		{
			return $config['host'].$separator.$config['port'];
		}

		return $config['host'];
	}

	private function appendIfConfigKeyAvailable($configKey, $targetKey, array $config, array $arguments)
	{
		if(isset($config[$configKey]))
		{
			$arguments[$targetKey] = $config[$configKey];
		}

		return $arguments;
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
