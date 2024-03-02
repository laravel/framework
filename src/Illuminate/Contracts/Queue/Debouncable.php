<?php

declare(strict_types = 1);

namespace Illuminate\Contracts\Queue;

interface Debouncable
{
    public function debounceFor(mixed $time, mixed $key = null);
}
