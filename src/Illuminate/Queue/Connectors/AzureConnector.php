<?php namespace Illuminate\Queue\Connectors;

use WindowsAzure\Common\ServicesBuilder;
use Illuminate\Queue\AzureQueue;

class AzureConnector implements ConnectorInterface {

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Queue\QueueInterface
	 */
	public function connect(array $config)
	{
		$azure = ServicesBuilder::getInstance()->createServiceBusService('Endpoint=' . $config['endpoint'] . ';SharedSecretIssuer=' . $config['issuer'] . ';SharedSecretValue=' . $config['secret']);

		return new AzureQueue($azure, $config['queue']);
	}

}