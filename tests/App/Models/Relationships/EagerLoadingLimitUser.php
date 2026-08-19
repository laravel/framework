<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class EagerLoadingLimitUser extends Model
{
    protected $table = 'users';

    public $timestamps = false;

    protected $guarded = [];

    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(EagerLoadingLimitComment::class, EagerLoadingLimitPost::class, 'user_id', 'post_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(EagerLoadingLimitPost::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }
}
