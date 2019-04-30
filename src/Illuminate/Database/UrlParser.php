<?php

namespace Illuminate\Database;

use function array_map;
use function parse_str;
use function parse_url;
use function array_merge;
use function preg_replace;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class UrlParser
{
    /**
     * The drivers aliases map.
     *
     * @var array
     */
    protected static $driverAliases = [
        'mssql' => 'sqlsrv',
        'mysql2' => 'mysql', // Amazon RDS, for some weird reason
        'postgres' => 'pgsql',
        'postgresql' => 'pgsql',
        'sqlite3' => 'sqlite',
    ];

    /**
     * The different components of parsed url.
     *
     * @var array
     */
    protected $parsedUrl;

    /**
     * Get all of the current drivers aliases.
     *
     * @return array
     */
    public static function getDriverAliases(): array
    {
        return static::$driverAliases;
    }

    /**
     * Add the driver alias to the driver aliases array.
     *
     * @param  string  $alias
     * @param  string  $driver
     * @return void
     */
    public static function addDriverAlias($alias, $driver)
    {
        static::$driverAliases[$alias] = $driver;
    }

    /**
     * Transform the url string or config array with url key to a parsed classic config array.
     *
     * @param  array|string  $config
     * @return array
     */
    public function parseDatabaseConfigWithUrl($config): array
    {
        if (is_string($config)) {
            $config = ['url' => $config];
        }

        $url = $config['url'] ?? null;
        $config = Arr::except($config, 'url');

        if (! $url) {
            return $config;
        }

        $this->parsedUrl = $this->parseUrl($url);

        return array_merge(
            $config,
            $this->getMainAttributes(),
            $this->getOtherOptions()
        );
    }

    /**
     * Decode the string url, to an array of all of its components.
     *
     * @param  string  $url
     * @return array
     */
    protected function parseUrl($url): array
    {
        // sqlite3?:///... => sqlite3?://null/... or else the URL will be invalid
        $url = preg_replace('#^(sqlite3?):///#', '$1://null/', $url);

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            throw new InvalidArgumentException('Malformed parameter "url".');
        }

        return $this->parseStringsToNativeTypes(array_map('rawurldecode', $parsedUrl));
    }

    /**
     * Convert string casted values to there native types.
     * Ex: 'false' => false, '42' => 42, 'foo' => 'foo'
     *
     * @param  string  $url
     * @return mixed
     */
    protected function parseStringsToNativeTypes($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'parseStringsToNativeTypes'], $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $parsedValue = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $parsedValue;
        }

        return $value;
    }

    /**
     * Return the main attributes of the database connection config from url.
     *
     * @return array
     */
    protected function getMainAttributes(): array
    {
        return array_filter([
            'driver' => $this->getDriver(),
            'database' => $this->getDatabase(),
            'host' => $this->getInUrl('host'),
            'port' => $this->getInUrl('port'),
            'username' => $this->getInUrl('user'),
            'password' => $this->getInUrl('pass'),
        ], function ($value) {
            return $value !== null;
        });
    }

    /**
     * Find connection driver from url.
     *
     * @return string|null
     */
    protected function getDriver()
    {
        $alias = $this->getInUrl('scheme');

        if (! $alias) {
            return null;
        }

        return static::$driverAliases[$alias] ?? $alias;
    }

    /**
     * Get a component of the parsed url.
     *
     * @param  string  $key
     * @return string|null
     */
    protected function getInUrl($key)
    {
        return $this->parsedUrl[$key] ?? null;
    }

    /**
     * Find connection database from url.
     *
     * @return string|null
     */
    protected function getDatabase()
    {
        $path = $this->getInUrl('path');

        if (! $path) {
            return null;
        }

        return substr($path, 1);
    }

    /**
     * Return all the options added to the url with query params.
     *
     * @return array
     */
    protected function getOtherOptions(): array
    {
        $queryString = $this->getInUrl('query');

        if (! $queryString) {
            return [];
        }

        $query = [];

        parse_str($queryString, $query);

        return $this->parseStringsToNativeTypes($query);
    }
}
