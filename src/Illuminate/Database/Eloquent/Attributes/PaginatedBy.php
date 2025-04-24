<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PaginatedBy
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<\Illuminate\Pagination\LengthAwarePaginator>|null  $lengthAware
     * @param  class-string<\Illuminate\Pagination\Paginator>|null  $simple
     * @param  class-string<\Illuminate\Pagination\CursorPaginator>|null  $cursor
     */
    public function __construct(
        public ?string $lengthAware = null,
        public ?string $simple = null,
        public ?string $cursor = null,
    ) {
    }
}
