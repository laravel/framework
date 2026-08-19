<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class UserWithUpdated extends Model
{
    public const CREATED_AT = null;

    protected $table = 'users_updated_at';

    protected $guarded = [];

    protected $dateFormat = 'U';
}
