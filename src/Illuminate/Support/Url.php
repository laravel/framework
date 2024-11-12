<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Arrayable;
use RuntimeException;
use Stringable;

class Url implements Arrayable, Stringable
{
    /**
     * Constructor.
     */
    public function __construct(
        public ?string $scheme = null,
        public ?string $host = null,
        public ?int $port = null,
        public ?string $user = null,
        #[\SensitiveParameter]
        public ?string $pass = null,
        public ?string $path = null,
        public ?UrlQueryParameters $query = null,
        public ?string $fragment = null,
    ) {
        $this->query ??= new UrlQueryParameters;
    }

    /**
     * Parse a URL string into a URL object.
     *
     * @param  string  $url
     * @return static
     */
    public static function parse(string $url): static
    {
        $components = parse_url($url);

        if ($components === false) {
            throw new RuntimeException("Invalid URL [$url].");
        }

        if (isset($components['query'])) {
            $components['query'] = static::query($components['query']);
        }

        return new static(...$components);
    }

    /**
     * Parse URL query parameters.
     *
     * @return \Illuminate\Support\UrlQueryParameters
     */
    protected static function query(?string $query): UrlQueryParameters
    {
        return UrlQueryParameters::parse($query);
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
            'query' => (string) $this->query,
            'fragment' => $this->fragment,
        ];
    }

    /**
     * Convert the URL object to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        $url = '';

        if ($this->scheme) {
            $url .= $this->scheme.':';
        }

        $url .= '//';

        if ($this->user) {
            $url .= $this->user.($this->pass ? ':'.$this->pass : '').'@';
        }

        if ($this->host) {
            $url .= $this->host;
        }

        if ($this->port) {
            $url .= ':'.$this->port;
        }

        if ($this->path) {
            $url .= $this->path;
        }

        if ($this->query) {
            $url .= '?'.$this->query;
        }

        if ($this->fragment) {
            $url .= '#'.$this->fragment;
        }

        return $url;
    }
}
