<?php

namespace Illuminate\Tests\App\Models\Serialization;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $guarded = [];
    public $timestamps = false;

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using(RoleUser::class);
    }
}
