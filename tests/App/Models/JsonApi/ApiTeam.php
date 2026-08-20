<?php

namespace Illuminate\Tests\App\Models\JsonApi;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Factories\JsonApi\TeamFactory;
use Illuminate\Tests\App\Http\Resources\JsonApi\TeamResource;

#[UseFactory(TeamFactory::class)]
#[UseResource(TeamResource::class)]
class ApiTeam extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'teams';

    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->belongsToMany(ApiUser::class, 'team_user', 'team_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps()
            ->using(Membership::class)
            ->as('membership');
    }
}
