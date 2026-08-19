<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[UseFactory(PivotWithoutTimestampRoleFactory::class)]
class PivotWithoutTimestampRole extends Model
{
    use HasFactory;

    protected $table = 'roles';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(PivotWithoutTimestampUser::class, 'role_user', 'role_id', 'user_id')
            ->withPivot('notes')
            ->using(PivotWithoutTimestampUserRole::class);
    }
}
