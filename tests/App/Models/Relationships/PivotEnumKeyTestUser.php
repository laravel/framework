<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PivotEnumKeyTestUser extends Model
{
    protected $table = 'users';
    public $timestamps = false;

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PivotEnumKeyTestRole::class, 'role_user', 'user_id', 'role_id');
    }
}
