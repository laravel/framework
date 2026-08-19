<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftDeletingRole extends Model
{
    use SoftDeletes;
    public $table = 'roles';
    protected $guarded = [];
}
