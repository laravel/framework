<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhereHasMorphPost extends Model
{
    use SoftDeletes;

    protected $table = 'posts';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeSomeSharedModelScope($query)
    {
        $query->where('title', '=', 'foo');
    }
}
