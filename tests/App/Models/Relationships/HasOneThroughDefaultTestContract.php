<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasOneThroughDefaultTestContract extends Model
{
    protected $table = 'contracts_default';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasOneThroughDefaultTestUser::class);
    }
}
