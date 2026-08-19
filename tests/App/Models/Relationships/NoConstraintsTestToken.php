<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class NoConstraintsTestToken extends Model
{
    public $timestamps = false;
    protected $table = 'tokens';
    protected $guarded = [];

    public function tokenable()
    {
        return $this->morphTo();
    }
}
