<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Arrayable;

class Url implements Arrayable
{
    /**
     * Constructor.
     */
    public function __construct(
        public $scheme = null,
        public $host = null,
        public $port = null,
        public $user = null,
        public $pass = null,
        public $path = null,
        public $query = null,
        public $fragment = null,
    ) {
    }

    /**
     * Parse a URL string into a URL object.
     *
     * @param  string  $url
     * @return static
     */
    public static function parse($url)
    {
        $components = parse_url($url) ?? [];

        return new static(
            $components['scheme'] ?? null,
            $components['host'] ?? null,
            $components['port'] ?? null,
            $components['user'] ?? null,
            $components['pass'] ?? null,
            $components['path'] ?? null,
            $components['query'] ?? null,
            $components['fragment'] ?? null,
        );
    }

    /**
     * Get the URL query parameters.
     *
     * @return \Illuminate\Support\UrlQueryParameters
     */
    public function query()
    {
        return UrlQueryParameters::parse($this->query);
    }

    /**
     * Convert the URL object to an array.
     *
     * @return array<string, string|null>
     */
    public function toArray()
    {
        return [
            'scheme' => $this->scheme,
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->user,
            'pass' => $this->pass,
            'path' => $this->path,
            'query' => $this->query,
            'fragment' => $this->fragment,
        ];
    }
}
