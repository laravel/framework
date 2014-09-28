<?php namespace Illuminate\Filesystem\Adapters;

use League\Flysystem\Adapter\Ftp;

class FtpConnector implements ConnectorInterface {

	/**
	 * Establish an adapter connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Ftp
	 */
	public function connect(array $config)
	{
		return $this->getAdapter($config);
	}

	/**
	 * Get the ftp adapter.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Ftp
	 */
	protected function getAdapter(array $config)
	{
		return new Ftp($config);
	}

}
