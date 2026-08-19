<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Concerns\HasAttributes;

class HasAttributesWithArrayCast
{
    use HasAttributes;

    public function getArrayableAttributes(): array
    {
        return ['foo' => ''];
    }

    public function getCasts(): array
    {
        return ['foo' => 'array'];
    }

    public function usesTimestamps(): bool
    {
        return false;
    }
}
