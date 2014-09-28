<?php namespace Illuminate\Filesystem\Adapters;

use League\Flysystem\Adapter\Sftp;

class SftpConnector implements ConnectorInterface {

	/**
	 * Establish an adapter connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Sftp
	 */
	public function connect(array $config)
	{
		return $this->getAdapter($config);
	}

	/**
	 * Get the sftp adapter.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Sftp
	 */
	protected function getAdapter(array $config)
	{
		return new Sftp($config);
	}

}
