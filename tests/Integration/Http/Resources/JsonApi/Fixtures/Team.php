<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(TeamFactory::class)]
class Team extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps()
            ->using(Membership::class)
            ->as('membership');
    }
}
