<?php

namespace Illuminate\Tests\App\Models\JsonApi;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Tests\App\Http\Resources\JsonApi\UserResource;
use Orchestra\Testbench\Factories\UserFactory;

#[UseResource(UserResource::class)]
#[UseFactory(UserFactory::class)]
class ApiUser extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }

    public function posts()
    {
        return $this->hasMany(ApiPost::class, 'user_id');
    }

    public function chaperonePosts()
    {
        return $this->hasMany(ApiPost::class, 'user_id')->chaperone('author');
    }

    public function comments()
    {
        return $this->hasMany(ApiComment::class, 'user_id');
    }

    public function teams()
    {
        return $this->belongsToMany(ApiTeam::class, 'team_user', 'user_id', 'team_id')
            ->withPivot('role')
            ->withTimestamps()
            ->using(Membership::class)
            ->as('membership');
    }
}
