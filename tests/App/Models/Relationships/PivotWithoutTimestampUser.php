<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Orchestra\Testbench\Factories\UserFactory;

#[UseFactory(UserFactory::class)]
class PivotWithoutTimestampUser extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(PivotWithoutTimestampRole::class, 'role_user', 'user_id', 'role_id')
            ->withPivot('notes')
            ->using(PivotWithoutTimestampUserRole::class)
            ->withTimestamps(updatedAt: false);
    }
}
