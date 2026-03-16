<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Sluggable
{
    /**
     * Create a new attribute instance.
     *
     * @param  array<int, string>|string  $from
     * @param  array<int, string>|string  $scope
     */
    public function __construct(
        public array|string $from = 'name',
        public string $column = 'slug',
        public string $separator = '-',
        public string $language = 'en',
        public array|string $scope = [],
        public bool $onUpdating = false,
        public bool $unique = true,
        public int $maxAttempts = 100,
        public ?int $maxLength = null,
    ) {
    }
}
