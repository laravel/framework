<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class AggregateUser extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'c', 'balance'];
    public $timestamps = false;
}
