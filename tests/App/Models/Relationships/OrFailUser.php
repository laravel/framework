<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrFailUser extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(OrFailRole::class, 'role_user', 'user_id', 'role_id');
    }
}
