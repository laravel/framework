<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class WhereHasMorphVideo extends Model
{
    protected $table = 'videos';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeSomeSharedModelScope($query)
    {
        $query->where('title', '=', 'foo');
    }
}
