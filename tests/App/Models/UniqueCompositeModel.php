<?php

namespace Illuminate\Tests\App\Models;

use Illuminate\Database\Eloquent\Model;

class UniqueCompositeModel extends Model
{
    protected $table = 'test_unique_constraint_composite';

    protected $fillable = ['first_name', 'last_name'];

    public $timestamps = false;
}
