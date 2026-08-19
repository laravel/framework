<?php

namespace Illuminate\Tests\App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseEloquentMySqlIntegrationUser extends Model
{
    protected $table = 'database_eloquent_mysql_integration_users';

    protected $guarded = [];
}
