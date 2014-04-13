<?php namespace Illuminate\Cache;

use Memcache;

class MemcacheConnector {

	/**
	 * Create a new Memcache connection.
	 *
	 * @param array  $servers
	 * @return \Memcache
	 */
	public function connect(array $servers)
	{
		$memcache = $this->getMemcache();

		// For each server in the array, we'll just extract the configuration and add
		// the server to the Memcache connection. Once we have added all of these
		// servers we'll verify the connection is successful and return it back.
		foreach ($servers as $server)
		{
			$memcache->addServer($server['host'], $server['port'], $server['weight']);
		}

		if ($memcache->getVersion() === false)
		{
			throw new \RuntimeException("Could not establish Memcached connection.");
		}

		return $memcache;
	}

	/**
	 * Get a new Memcached instance.
	 *
	 * @return \Memcached
	 */
	protected function getMemcache()
	{
		return new Memcache;
	}

}
