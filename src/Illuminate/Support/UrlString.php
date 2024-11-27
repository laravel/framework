<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Arrayable;
use Stringable;

class UrlString implements Stringable, Arrayable
{
    /**
     * The URL parts.
     */
    protected array $parts = [
        'scheme' => null,
        'user' => null,
        'pass' => null,
        'host' => null,
        'port' => null,
        'path' => null,
        'query' => [],
        'fragment' => null,
    ];

    /**
     * Create a new URL string instance.
     */
    public function __construct(string $url = '')
    {
        if (! empty($url)) {
            $parts = parse_url($url);

            parse_str($parts['query'] ?? '', $query);

            $parts['query'] = $query;

            $this->parts = array_replace($this->parts, $parts);
        }
    }

    /**
     * Get the scheme.
     */
    public function getScheme(): ?string
    {
        return $this->parts['scheme'];
    }

    /**
     * Set the scheme.
     */
    public function setScheme(string $value): static
    {
        $this->parts['scheme'] = $value;

        return $this;
    }

    /**
     * Get the protocol.
     */
    public function getProtocol(): ?string
    {
        return match ($this->parts['scheme']) {
            '', null => null,
            'file' => 'file:///',
            default => sprintf('%s://', $this->parts['scheme']),
        };
    }

    /**
     * Get the password.
     */
    public function getPass(): ?string
    {
        return $this->parts['pass'];
    }

    /**
     * Set the password.
     */
    public function setPass(string $value): static
    {
        $this->parts['pass'] = $value;

        return $this;
    }

    /**
     * Get the username.
     */
    public function getUser(): ?string
    {
        return $this->parts['user'];
    }

    /**
     * Set the username.
     */
    public function setUser(string $value): static
    {
        $this->parts['user'] = $value;

        return $this;
    }

    /**
     * Set the auth credentials.
     */
    public function withAuth(string $username, string $password): static
    {
        return $this->setUser($username)->setPass($password);
    }

    /**
     * Get the hostname.
     */
    public function getHost(): ?string
    {
        return $this->parts['host'];
    }

    /**
     * Set the hostname.
     */
    public function setHost(string $value): static
    {
        $this->parts['host'] = $value;

        return $this;
    }

    /**
     * Get the port.
     */
    public function getPort(): ?int
    {
        return $this->parts['port'];
    }

    /**
     * Set the port.
     */
    public function setPort(int $value): static
    {
        $this->parts['port'] = $value;

        return $this;
    }

    /**
     * Get the path.
     */
    public function getPath(): ?string
    {
        return $this->parts['path'];
    }

    /**
     * Set the path.
     */
    public function setPath(string $value): static
    {
        $this->parts['path'] = '/'.ltrim($value, '/');

        return $this;
    }

    /**
     * Get the query as an array.
     */
    public function getQuery(): array
    {
        return $this->parts['query'];
    }

    /**
     * Set the query.
     */
    public function setQuery(string|array $value): static
    {
        if (is_string($value)) {
            parse_str($value, $query);

            $this->parts['query'] = $query;
        } else {
            $this->parts['query'] = $value;
        }

        return $this;
    }

    /**
     * Get the query string.
     */
    public function getQueryString(): string
    {
        return http_build_query((array) $this->parts['query'], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Merge the given value into the query.
     */
    public function withQuery(array $value): static
    {
        $this->parts['query'] = array_replace((array) $this->parts['query'], $value);

        return $this;
    }

    /**
     * Remove the given keys from the query.
     */
    public function withoutQuery(array|string|null $keys = null): static
    {
        $this->parts['query'] = match (true) {
            is_null($keys) => [],
            default => array_diff_key((array) $this->parts['query'], array_flip((array) $keys)),
        };

        return $this;
    }

    /**
     * Get the fragment.
     */
    public function getFragment(): ?string
    {
        return $this->parts['fragment'];
    }

    /**
     * Set the fragment.
     */
    public function setFragment(string $value): static
    {
        $this->parts['fragment'] = $value;

        return $this;
    }

    /**
     * Build the URL from the parts.
     */
    public function build(): string
    {
        $url = isset($this->parts['scheme']) ? $this->getProtocol() : '';
        $url .= $this->buildAuth();
        $url .= $this->parts['host'] ?? '';
        $url .= $this->buildPort();
        $url .= $this->parts['path'] ?? '';
        $url .= $this->buildQuery();
        $url .= $this->buildFragment();

        return $url;
    }

    /**
     * Build the auth URL part.
     */
    protected function buildAuth(): string
    {
        return isset($this->parts['user'], $this->parts['pass'])
            ? sprintf('%s:%s@', $this->parts['user'], $this->parts['pass'])
            : '';
    }

    /**
     * Build the port URL part.
     */
    protected function buildPort(): string
    {
        return isset($this->parts['port']) ? sprintf(':%d', $this->parts['port']) : '';
    }

    /**
     * Build the query URL part.
     */
    protected function buildQuery(): string
    {
        return ! empty($this->parts['query'])
            ? sprintf('?%s', $this->getQueryString())
            : '';
    }

    /**
     * Build the fragment URL part.
     */
    protected function buildFragment(): string
    {
        return isset($this->parts['fragment'])
            ? sprintf('#%s', $this->parts['fragment'])
            : '';
    }

    /**
     * Convert the object to an array.
     */
    public function toArray(): array
    {
        return $this->parts;
    }

    /**
     * Convert the object to a string.
     */
    public function __toString(): string
    {
        return $this->build();
    }
}
