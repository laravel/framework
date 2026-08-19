<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class WherePivotClosureUser extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
}
