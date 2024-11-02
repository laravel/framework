<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Arrayable;

class Url implements Arrayable
{
    /**
     * Constructor.
     */
    public function __construct(
        public ?string $scheme = null,
        public ?string $host = null,
        public ?string $port = null,
        public ?string $user = null,
        public ?string $pass = null,
        public ?string $path = null,
        public ?string $query = null,
        public ?string $fragment = null,
    ) {
    }

    /**
     * Parse a URL string into a URL object.
     *
     * @param  string  $url
     * @return static
     */
    public static function parse(string $url): static
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
    public function query(): UrlQueryParameters
    {
        return UrlQueryParameters::parse($this->query);
    }

    /**
     * Convert the URL object to an array.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
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
