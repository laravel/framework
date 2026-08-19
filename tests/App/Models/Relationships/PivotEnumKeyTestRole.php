<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PivotEnumKeyTestRole extends Model
{
    protected $table = 'roles';
    protected $fillable = ['id', 'name'];
    public $timestamps = false;
}
