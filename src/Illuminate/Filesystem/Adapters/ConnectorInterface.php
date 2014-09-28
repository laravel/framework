<?php namespace Illuminate\Filesystem\Adapters;

interface ConnectorInterface {

	/**
	 * Establish a connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\AdapterInterface
	 */
	public function connect(array $config);

}
