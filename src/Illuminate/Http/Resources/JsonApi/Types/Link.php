<?php

namespace Illuminate\Http\Resources\JsonApi\Types;

use JsonSerializable;

class Link implements JsonSerializable
{
    /**
     * Construct a new link.
     *
     * @param  string  $type
     * @param  string  $href
     * @param  array  $meta
     */
    public function __construct(
        public string $type,
        public string $href,
        public array $meta = []
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     * @return static
     */
    public static function to(string $href, array $meta = [])
    {
        return new static('self', $href, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return static
     */
    public static function related(string $href, array $meta = [])
    {
        return new static('related', $href, $meta);
    }

    /**
     * Prepare the link for JSON serialization.
     *
     * @return array{href: string, meta?: object}
     */
    public function jsonSerialize(): array
    {
        return [
            'href' => $this->href,
            ...$this->meta ? ['meta' => (object) $this->meta] : [],
        ];
    }
}
