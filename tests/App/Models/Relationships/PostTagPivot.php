<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class PostTagPivot extends Pivot
{
    protected $table = 'posts_tags';

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('U');
    }

    public function scopeActive($query)
    {
        return $query->where('flag', 'foo');
    }
}
