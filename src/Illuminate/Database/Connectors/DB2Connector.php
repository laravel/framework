<?php namespace Illuminate\Database\Connectors;

class DB2Connector extends Connector implements ConnectorInterface {

	/**
	 * Establish a database connection.
	 *
	 * @param  array  $options
	 * @return PDO
	 */
	public function connect(array $config)
	{
		$dsn = $this->getDsn($config);

		// We need to grab the PDO options that should be used while making the brand
		// new connection instance. The PDO options control various aspects of the
		// connection's behavior, and some might be specified by the developers.
		$options = $this->getOptions($config);

		return $this->createConnection($this->getDsn($config), $config, $options);
	}

	/**
	 * Create a DSN string from a configuration.
	 *
	 * TODO: Figure out ODBC for Mac
	 *
	 * @param  array   $config
	 * @return string
	 */
	protected function getDsn(array $config)
	{
		// First we will create the basic DSN setup as well as the port if it is in
		// in the configuration options. This will give us the basic DSN we will
		// need to establish the PDO connections and return them back for use.
		// NOTE: This PDO connection MAY be able to get used as a fallback for 
		// SQLServer
		extract($config);

		// NOTE: This will 100% require pdo_odbc functionality and ODBC drivers on 
		// your machine - Windoze; you're fine - OSX ;-(
		$dsn = "odbc:Driver={iSeries Access ODBC Driver};System=$host;Naming=1;Database=$database"; // Not sure what naming does

		if (isset($library) && $library !== '') {
			$dsn .= ";Dbq=$library";
		}

		if (isset($port) && is_integer($port)) {
			$dsn .= ";Port=$port";
		}

		return $dsn;
	}

}