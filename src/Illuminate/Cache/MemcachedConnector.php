<?php namespace Illuminate\Cache;

use Memcached;
use RuntimeException;

class MemcachedConnector {

	/**
	 * Create a new Memcached connection.
	 *
	 * @param  array  $servers
	 * @param  string $persistentConnectionId
	 * @param  array  $customOptions
	 * @param  array  $saslCredentials
	 * @return \Memcached
	 *
	 * @throws \RuntimeException
	 */
	public function connect(array $servers, $persistentConnectionId = false, array $customOptions = [], array $saslCredentials = [])
	{
		$memcached = $this->getMemcached($persistentConnectionId);

		// Validate and set custom options
		if (count($customOptions))
		{
			$memcachedConstants = array_map(
				function ($option)
				{
					$constant = "Memcached::{$option}";
					if (!defined($constant))
					{
						throw new RuntimeException("Invalid Memcached option: [{$constant}]");
					}

					return constant($constant);
				},
				array_keys($customOptions)
			);
			$memcached->setOptions(array_combine($memcachedConstants, $customOptions));
		}

		// Set SASL auth data, requires binary protocol
		if (count($saslCredentials) == 2)
		{
			list($username, $password) = $saslCredentials;
			$memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
			$memcached->setSaslAuthData($username, $password);
		}

		// Only add servers if we need to. If using a persistent connection
		// the servers must only be added the first time otherwise connections
		// are duplicated.
		if (!$memcached->getServerList())
		{
			foreach ($servers as $server)
			{
				$memcached->addServer(
					$server['host'], $server['port'], $server['weight']
				);
			}
		}

		// Verify connection
		$memcachedStatus = $memcached->getVersion();
		if (!is_array($memcachedStatus))
		{
			throw new RuntimeException("No Memcached servers added.");
		}
		if (in_array('255.255.255', $memcachedStatus) && count(array_unique($memcachedStatus)) === 1)
		{
			throw new RuntimeException("Could not establish Memcached connection.");
		}

		return $memcached;
	}

	/**
	 * Get a new Memcached instance.
	 *
	 * @return \Memcached
	 */
	protected function getMemcached($persistentConnectionId)
	{
		if ($persistentConnectionId !== false)
		{
			return new Memcached($persistentConnectionId);
		}

		return new Memcached;
	}

}
