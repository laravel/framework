<?php

namespace Illuminate\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Cors
{
    /**
     * @param  array<string>|null  $origins
     * @param  array<string>|null  $methods
     * @param  array<string>|null  $headers
     * @param  array<string>|null  $exposed_headers
     * @param  int|null  $max_age
     * @param  bool|null  $credentials
     */
    public function __construct(
        public ?array $origins = null,
        public ?array $methods = null,
        public ?array $headers = null,
        public ?array $exposed_headers = null,
        public ?int $max_age = null,
        public ?bool $credentials = null,
    ) {
    }

    /**
     * Get the CORS options as an array, excluding null values.
     *
     * @return array{
     *     origins?: array<string>,
     *     methods?: array<string>,
     *     headers?: array<string>,
     *     exposed_headers?: array<string>,
     *     max_age?: int,
     *     credentials?: bool,
     * }
     */
    public function toArray(): array
    {
        return array_filter([
            'origins' => $this->origins,
            'methods' => $this->methods,
            'headers' => $this->headers,
            'exposed_headers' => $this->exposed_headers,
            'max_age' => $this->max_age,
            'credentials' => $this->credentials,
        ], fn ($value) => ! is_null($value));
    }
}
