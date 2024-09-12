<?php

namespace Illuminate\Tests\Integration\Database\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DatabaseEloquentIntegrationUniqueUserUUID extends Model
{
    use HasUuids;

    protected $table = 'database_eloquent_integration_unique_users_uuid';
    protected $keyType = 'string';
    protected $casts = ['birthday' => 'datetime'];
    protected $guarded = [];
}
