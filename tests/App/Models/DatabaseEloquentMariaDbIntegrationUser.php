<?php

namespace Illuminate\Tests\App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseEloquentMariaDbIntegrationUser extends Model
{
    protected $table = 'database_eloquent_mariadb_integration_users';

    protected $guarded = [];
}
