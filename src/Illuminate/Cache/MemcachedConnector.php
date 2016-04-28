<?php

namespace Illuminate\Cache;

use Memcached;
use RuntimeException;

class MemcachedConnector
{
    /**
     * Create a new Memcached connection.
     *
     * @param  array  $servers
     * @param string|null $persistentConnectionId
     * @param array $customOptions
     * @param array $saslCredentials
     *
     * @throws \RuntimeException
     *
     * @return \Memcached
     */
    public function connect(
        array $servers,
        $persistentConnectionId = null,
        array $customOptions = [],
        array $saslCredentials = []
    ) {
        $memcached = $this->getMemcached($persistentConnectionId);

        if (count($customOptions)) {
            $memcached->setOptions($this->validateCustomOptions($customOptions));
        }

        // Set SASL auth data.
        if (count($saslCredentials) == 2) {
            list($username, $password) = $saslCredentials;
            $memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $memcached->setSaslAuthData($username, $password);
        }

        // Add servers if necessary. When using a persistent connection servers
        // must only be added once otherwise connections are duplicated.
        if (! $memcached->getServerList()) {
            foreach ($servers as $server) {
                $memcached->addServer(
                    $server['host'], $server['port'], $server['weight']
                );
            }
        }

        $memcachedStatus = $memcached->getVersion();

        if (! is_array($memcachedStatus)) {
            throw new RuntimeException('No Memcached servers added.');
        }

        if (in_array('255.255.255', $memcachedStatus) && count(array_unique($memcachedStatus)) === 1) {
            throw new RuntimeException('Could not establish Memcached connection.');
        }

        return $memcached;
    }

    /**
     * Get a new Memcached instance.
     *
     * @param string|null $persistentConnectionId
     * @return \Memcached
     */
    protected function getMemcached($persistentConnectionId)
    {
        if (is_string($persistentConnectionId) && strlen($persistentConnectionId)) {
            return new Memcached($persistentConnectionId);
        }

        return new Memcached();
    }

    /**
     * Validates memcached custom options and resolves them in to
     * Memcached constants for use by Memcached::setOptions().
     *
     * @param array $customOptions
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    protected function validateCustomOptions(array $customOptions)
    {
        $memcachedConstants = array_map(
            function ($option) {
                $constant = "Memcached::{$option}";
                if (! defined($constant)) {
                    throw new RuntimeException("Invalid Memcached option: [{$constant}]");
                }

                return constant($constant);
            },
            array_keys($customOptions)
        );

        return array_combine($memcachedConstants, $customOptions);
    }
}
