<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class OrFailRole extends Model
{
    protected $table = 'roles';
    protected $guarded = [];
    public $timestamps = false;
}
