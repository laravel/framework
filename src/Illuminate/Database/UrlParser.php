<?php

namespace Illuminate\Database;

use function array_map;
use function parse_str;
use function parse_url;
use function array_merge;
use function preg_replace;
use function array_key_exists;

class UrlParser
{
    private const DRIVER_ALIASES = [
        'mssql' => 'sqlsrv',
        'sqlsrv' => 'sqlsrv',
        'mysql' => 'mysql',
        'mysql2' => 'mysql', // Amazon RDS, for some weird reason
        'postgres' => 'pgsql',
        'postgresql' => 'pgsql',
        'pgsql' => 'pgsql',
        'sqlite' => 'sqlite',
        'sqlite3' => 'sqlite',
    ];
    /**
     * @var array
     */
    private $url;

    public function __construct(?string $url)
    {
        $this->url = $this->getParsedUrl($url);
    }

    private function getParsedUrl(?string $url)
    {
        // sqlite3?:///... => sqlite3?://localhost/... or else the URL will be invalid
        $url = preg_replace('#^(sqlite3?):///#', '$1://localhost/', $url);

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            throw new \InvalidArgumentException('Malformed parameter "url".');
        }

        return array_map('rawurldecode', $parsedUrl);
    }

    public static function parse(?string $url): array
    {
        return (new self($url))->parseDatabaseUrl();
    }

    public function parseDatabaseUrl(): array
    {
        return array_merge(
            $this->getMainAttributes(),
            $this->parseDatabaseUrlQuery()
        );
    }

    private function getMainAttributes(): array
    {
        return [
            'driver' => $this->getDriverFromAlias($this->getInUrl('scheme')),
            'database' => $this->normalizeDatabaseUrlPath($this->getInUrl('path')),
            'host' => $this->getInUrl('host'),
            'port' => $this->getInUrl('port'),
            'username' => $this->getInUrl('user'),
            'password' => $this->getInUrl('pass'),
        ];
    }

    private function getDriverFromAlias(?string $alias): ?string
    {
        if (! $alias) {
            return null;
        }

        if (! array_key_exists($alias, self::DRIVER_ALIASES)) {
            throw new \InvalidArgumentException('No driver found with "'.$alias.'" scheme');
        }

        return self::DRIVER_ALIASES[$alias];
    }

    private function getInUrl(string $key): ?string
    {
        return $this->url[$key] ?? null;
    }

    private function normalizeDatabaseUrlPath(?string $urlPath): ?string
    {
        if (! $urlPath) {
            return null;
        }

        return trim($urlPath, '/');
    }

    private function parseDatabaseUrlQuery(): array
    {
        $queryString = $this->getInUrl('query');

        if (! $queryString) {
            return [];
        }

        $query = [];

        parse_str($queryString, $query);

        return $query;
    }
}
