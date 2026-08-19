<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class UserWithCreated extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'users_created_at';

    protected $guarded = [];

    protected $dateFormat = 'U';
}
