<?php

namespace Illuminate\Tests\App\Models;

use Illuminate\Database\Eloquent\Model;

class UniqueSingleModel extends Model
{
    protected $table = 'test_unique_constraint';

    protected $fillable = ['name'];

    public $timestamps = false;
}
