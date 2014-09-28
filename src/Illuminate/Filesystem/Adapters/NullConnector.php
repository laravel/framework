<?php namespace Illuminate\Filesystem\Adapters;

use League\Flysystem\Adapter\NullAdapter;

class NullConnector implements ConnectorInterface {

	/**
	 * Establish an adapter connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\NullAdapter
	 */
	public function connect(array $config)
	{
		return $this->getAdapter();
	}

	/**
	 * Get the null adapter.
	 *
	 * @return \League\Flysystem\Adapter\NullAdapter
	 */
	protected function getAdapter()
	{
		return new NullAdapter();
	}

}
