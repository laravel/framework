<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphEagerLoadAction extends Model
{
    protected $table = 'actions';

    public $timestamps = false;

    public function target()
    {
        return $this->morphTo()->withTrashed();
    }
}
