<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Foundation\Application;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Env;
use Redis;
use Throwable;
use UnexpectedValueException;

trait InteractsWithRedis
{
    /**
     * Indicates connection failed if redis is not available.
     *
     * @var bool
     */
    private static $connectionFailedOnceWithDefaultsSkip = false;

    /**
     * Redis manager instance.
     *
     * @var \Illuminate\Redis\RedisManager[]
     */
    private $redisManagers = [];

    /**
     * Teardown redis connection.
     *
     * @return void
     */
    public function tearDownRedis()
    {
        /** @var \Illuminate\Redis\RedisManager $redisManager */
        foreach ($this->redisManagers as $label => $redisManager) {
            $redisManager->connection()->flushdb();
            $redisManager->connection()->disconnect();
        }
    }

    /**
     * Builds a redis manager from a predefined list of available connection
     * configurations.
     *
     * If a driver and a config are given, they are used to create a new redis
     * connection instead of the defaulting to a predefined list of connections.
     * This way you can also create for example cluster or a very customized
     * redis connection.
     *
     * @param  string  $connection  Connection label.
     * @param  string  $driver  Optional driver to use together with a config.
     * @param  array  $config  Optional config to use for the connection.
     * @return \Illuminate\Redis\RedisManager
     */
    public function getRedisManager($connection, $driver = 'phpredis', $config = [])
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped(
                'The redis extension is not installed. Please install the extension to enable '.__CLASS__
            );
        }

        if (static::$connectionFailedOnceWithDefaultsSkip) {
            $this->markTestSkipped(
                'Trying default host/port failed, please set environment variable '.
                'REDIS_HOST & REDIS_PORT to enable '.__CLASS__
            );
        }

        if (! empty($config)) {
            return $this->redisManagers[$connection] = $this->initializeRedisManager($driver, $config);
        }

        if (array_key_exists($connection, $this->redisManagers)) {
            return $this->redisManagers[$connection];
        }

        $config = [
            'cluster' => false,
            'default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'port' => (int) env('REDIS_PORT', 6379),
                'timeout' => 0.5,
                'database' => 5,
                'options' => [
                    'name' => 'base',
                ],
            ],
        ];

        switch ($connection) {
            case 'predis':
                $driver = 'predis';
                $config['default']['options']['name'] = 'predis';
                break;
            case 'phpredis':
                $config['default']['options']['name'] = 'phpredis';
                break;
            case 'phpredis_url':
                $config['default']['options']['name'] = 'phpredis_url';
                $config['default']['url'] = "redis://user@{$config['default']['host']}:{$config['default']['port']}";
                $config['default']['host'] = 'overwrittenByUrl';
                $config['default']['port'] = 'overwrittenByUrl';
                break;
            case 'phpredis_prefix':
                $config['default']['options']['name'] = 'phpredis_prefix';
                $config['default']['options']['prefix'] = 'laravel:';
                break;
            case 'phpredis_persistent':
                $config['default']['options']['name'] = 'phpredis_persistent';
                $config['default']['persistent'] = true;
                $config['default']['persistent_id'] = 'laravel';
                break;
            case 'phpredis_scan_noretry':
                $config['default']['options']['name'] = 'phpredis_scan_noretry';
                $config['default']['options']['scan'] = Redis::SCAN_NORETRY;
                break;
            case 'phpredis_scan_retry':
                $config['default']['options']['name'] = 'phpredis_scan_retry';
                $config['default']['options']['scan'] = Redis::SCAN_RETRY;
                break;
            case 'phpredis_scan_prefix':
                $config['default']['options']['name'] = 'phpredis_scan_prefix';
                $config['default']['options']['scan'] = Redis::SCAN_PREFIX;
                break;
            case 'phpredis_scan_noprefix':
                $config['default']['options']['name'] = 'phpredis_scan_noprefix';
                $config['default']['options']['scan'] = Redis::SCAN_NOPREFIX;
                break;
            case 'phpredis_serializer_none':
                $config['default']['options']['name'] = 'phpredis_serializer_none';
                $config['default']['options']['serializer'] = Redis::SERIALIZER_NONE;
                break;
            case 'phpredis_serializer_php':
                $config['default']['options']['name'] = 'phpredis_serializer_php';
                $config['default']['options']['serializer'] = Redis::SERIALIZER_PHP;
                break;
            case 'phpredis_serializer_igbinary':
                $config['default']['options']['name'] = 'phpredis_serializer_igbinary';
                $config['default']['options']['serializer'] = Redis::SERIALIZER_IGBINARY;
                break;
            case 'phpredis_serializer_json':
                $config['default']['options']['name'] = 'phpredis_serializer_json';
                $config['default']['options']['serializer'] = Redis::SERIALIZER_JSON;
                break;
            case 'phpredis_serializer_msgpack':
                $config['default']['options']['name'] = 'phpredis_serializer_msgpack';
                $config['default']['options']['serializer'] = Redis::SERIALIZER_MSGPACK;
                break;
            case 'phpredis_compression_lzf':
                $config['default']['options']['name'] = 'phpredis_compression_lzf';
                $config['default']['options']['compression'] = Redis::COMPRESSION_LZF;
                break;
            case 'phpredis_compression_zstd':
                $config['default']['options']['name'] = 'phpredis_compression_zstd';
                $config['default']['options']['compression'] = Redis::COMPRESSION_ZSTD;
                break;
            case 'phpredis_compression_zstd_default':
                $config['default']['options']['name'] = 'phpredis_compression_zstd_default';
                $config['default']['options']['compression'] = Redis::COMPRESSION_ZSTD;
                $config['default']['options']['compression_level'] = Redis::COMPRESSION_ZSTD_DEFAULT;
                break;
            case 'phpredis_compression_zstd_min':
                $config['default']['options']['name'] = 'phpredis_compression_zstd_min';
                $config['default']['options']['compression'] = Redis::COMPRESSION_ZSTD;
                $config['default']['options']['compression_level'] = Redis::COMPRESSION_ZSTD_MIN;
                break;
            case 'phpredis_compression_zstd_max':
                $config['default']['options']['name'] = 'phpredis_compression_zstd_max';
                $config['default']['options']['compression'] = Redis::COMPRESSION_ZSTD;
                $config['default']['options']['compression_level'] = Redis::COMPRESSION_ZSTD_MAX;
                break;
            case 'phpredis_compression_lz4':
                $config['default']['options']['name'] = 'phpredis_compression_lz4';
                $config['default']['options']['compression'] = Redis::COMPRESSION_LZ4;
                break;
            case 'phpredis_compression_lz4_default':
                $config['default']['options']['name'] = 'phpredis_compression_lz4_default';
                $config['default']['options']['compression'] = Redis::COMPRESSION_LZ4;
                $config['default']['options']['compression_level'] = 0;
                break;
            case 'phpredis_compression_lz4_min':
                $config['default']['options']['name'] = 'phpredis_compression_lz4_min';
                $config['default']['options']['compression'] = Redis::COMPRESSION_LZ4;
                $config['default']['options']['compression_level'] = 1;
                break;
            case 'phpredis_compression_lz4_max':
                $config['default']['options']['name'] = 'phpredis_compression_lz4_max';
                $config['default']['options']['compression'] = Redis::COMPRESSION_LZ4;
                $config['default']['options']['compression_level'] = 12;
                break;
            case 'phpredis_msgpack_and_lz4':
                $config['default']['options']['name'] = 'phpredis_msgpack_and_lz4';
                $config['default']['options']['serializer'] = Redis::SERIALIZER_MSGPACK;
                $config['default']['options']['compression'] = Redis::COMPRESSION_LZ4;
                $config['default']['options']['compression_level'] = 12;
                break;
            default:
                throw new UnexpectedValueException(sprintf(
                    'Redis manager connection configuration %s is not defined.',
                    $connection,
                ));
        }

        return $this->redisManagers[$connection] = $this->initializeRedisManager($driver, $config);
    }

    /**
     * Returns a list of available redis connections.
     *
     * @return array
     */
    public function getRedisConnections()
    {
        return [
            'predis',
            'phpredis',
        ];
    }

    /**
     * Returns an extended list of available redis connections.
     *
     * @return array
     */
    public function getExtendedRedisConnections()
    {
        $connections = [
            'predis',
            'phpredis',
            'phpredis_url',
            'phpredis_prefix',
            'phpredis_persistent',
        ];

        if (defined('Redis::SCAN_NORETRY')) {
            $connections[] = 'phpredis_scan_noretry';
        }

        if (defined('Redis::SCAN_RETRY')) {
            $connections[] = 'phpredis_scan_retry';
        }

        if (defined('Redis::SCAN_PREFIX')) {
            $connections[] = 'phpredis_scan_prefix';
        }

        if (defined('Redis::SCAN_NOPREFIX')) {
            $connections[] = 'phpredis_scan_noprefix';
        }

        if (defined('Redis::SERIALIZER_NONE')) {
            $connections[] = 'phpredis_serializer_none';
        }

        if (defined('Redis::SERIALIZER_PHP')) {
            $connections[] = 'phpredis_serializer_php';
        }

        if (defined('Redis::SERIALIZER_IGBINARY')) {
            $connections[] = 'phpredis_serializer_igbinary';
        }

        if (defined('Redis::SERIALIZER_JSON')) {
            $connections[] = 'phpredis_serializer_json';
        }

        if (defined('Redis::SERIALIZER_MSGPACK')) {
            $connections[] = 'phpredis_serializer_msgpack';
        }

        if (defined('Redis::COMPRESSION_LZF')) {
            $connections[] = 'phpredis_compression_lzf';
        }

        if (defined('Redis::COMPRESSION_ZSTD')) {
            $connections[] = 'phpredis_compression_zstd';
            $connections[] = 'phpredis_compression_zstd_default';
            $connections[] = 'phpredis_compression_zstd_min';
            $connections[] = 'phpredis_compression_zstd_max';
        }

        if (defined('Redis::COMPRESSION_LZ4')) {
            $connections[] = 'phpredis_compression_lz4';
            $connections[] = 'phpredis_compression_lz4_default';
            $connections[] = 'phpredis_compression_lz4_min';
            $connections[] = 'phpredis_compression_lz4_max';
        }

        if (defined('Redis::SERIALIZER_MSGPACK') && defined('Redis::COMPRESSION_LZ4')) {
            $connections[] = 'phpredis_msgpack_and_lz4';
        }

        return $connections;
    }

    /**
     * Data provider for tests that lists a default set of redis connections.
     *
     * @return array
     */
    public function redisConnectionDataProvider()
    {
        return (new Collection($this->getRedisConnections()))->mapWithKeys(function ($label) {
            return [
                $label => [
                    $label,
                ],
            ];
        })->all();
    }

    /**
     * Extended data provider for tests that also lists special configurations
     * like serialization and compression support on phpredis.
     *
     * @return array
     */
    public function extendedRedisConnectionDataProvider()
    {
        return (new Collection($this->getExtendedRedisConnections()))->mapWithKeys(function ($label) {
            return [
                $label => [
                    $label,
                ],
            ];
        })->all();
    }

    /**
     * Initializes a new RedisManager with the given driver and config.
     *
     * @param  string  $driver
     * @param  array  $config
     * @return \Illuminate\Redis\RedisManager
     */
    private function initializeRedisManager($driver, $config)
    {
        $app = $this->app ?? new Application();
        $redisManager = new RedisManager($app, $driver, $config);

        try {
            $redisManager->connection()->flushdb();
        } catch (Throwable $exception) {
            if (
                $config['default']['host'] === '127.0.0.1' &&
                $config['default']['port'] === 6379 &&
                Env::get('REDIS_HOST') === null
            ) {
                static::$connectionFailedOnceWithDefaultsSkip = true;

                $this->markTestSkipped(
                    'Trying default host/port failed, please set environment variable '.
                    'REDIS_HOST & REDIS_PORT to enable '.__CLASS__
                );
            }

            throw $exception;
        }

        return $redisManager;
    }
}
