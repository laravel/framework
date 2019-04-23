<?php

namespace Illuminate\Database;

use Illuminate\Support\Arr;
use function array_map;
use function array_merge;
use function parse_str;
use function parse_url;
use function preg_replace;

class UrlParser
{
    private static $driverAliases = [
        'mssql' => 'sqlsrv',
        'mysql2' => 'mysql', // Amazon RDS, for some weird reason
        'postgres' => 'pgsql',
        'postgresql' => 'pgsql',
        'sqlite3' => 'sqlite',
    ];

    /**
     * @var array
     */
    private $parsedUrl;

    public static function getDriverAliases(): array
    {
        return self::$driverAliases;
    }

    public static function addDriverAlias(string $alias, string $driver)
    {
        self::$driverAliases[$alias] = $driver;
    }

    /**
     * @param array|string $config
     *
     * @return array
     */
    public function parseDatabaseConfigWithUrl($config): array
    {
        if (is_string($config)) {
            $config = ['url' => $config];
        }

        $url = $config['url'] ?? null;
        $config = Arr::except($config, 'url');

        if (!$url) {
            return $config;
        }

        $this->parsedUrl = $this->parseUrl($url);

        return array_merge(
            $config,
            $this->getMainAttributes(),
            $this->getOtherOptions()
        );
    }

    private function parseUrl(string $url): array
    {
        // sqlite3?:///... => sqlite3?://null/... or else the URL will be invalid
        $url = preg_replace('#^(sqlite3?):///#', '$1://null/', $url);

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            throw new \InvalidArgumentException('Malformed parameter "url".');
        }

        return $this->parseStringsToNativeTypes(array_map('rawurldecode', $parsedUrl));
    }

    private function parseStringsToNativeTypes($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'parseStringsToNativeTypes'], $value);
        }

        if (!is_string($value)) {
            return $value;
        }

        $parsedValue = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $parsedValue;
        }

        return $value;
    }

    private function getMainAttributes(): array
    {
        return array_filter([
            'driver' => $this->getDriver(),
            'database' => $this->getNormalizedPath(),
            'host' => $this->getInUrl('host'),
            'port' => $this->getInUrl('port'),
            'username' => $this->getInUrl('user'),
            'password' => $this->getInUrl('pass'),
        ], function ($value) {
            return $value !== null;
        });
    }

    private function getDriver(): ?string
    {
        $alias = $this->getInUrl('scheme');

        if (!$alias) {
            return null;
        }

        return self::$driverAliases[$alias] ?? $alias;
    }

    private function getInUrl(string $key): ?string
    {
        return $this->parsedUrl[$key] ?? null;
    }

    private function getNormalizedPath(): ?string
    {
        $path = $this->getInUrl('path');

        if (!$path) {
            return null;
        }

        return substr($path, 1);
    }

    private function getOtherOptions(): array
    {
        $queryString = $this->getInUrl('query');

        if (!$queryString) {
            return [];
        }

        $query = [];

        parse_str($queryString, $query);

        return $this->parseStringsToNativeTypes($query);
    }
}
