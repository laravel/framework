<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Tests\App\Models\Scopes\UserWithGlobalScope;

class Team extends Model
{
    public $table = 'teams';
    public $timestamps = false;
    protected $guarded = [];

    public function members()
    {
        return $this->hasMany(User::class, 'team_id');
    }

    public function membersWithGlobalScope()
    {
        return $this->hasMany(UserWithGlobalScope::class, 'team_id');
    }

    public function articles()
    {
        return $this->hasManyThrough(Article::class, User::class);
    }

    public function latestArticle(): HasOneThrough
    {
        return $this->articles()->one()->latest();
    }
}
