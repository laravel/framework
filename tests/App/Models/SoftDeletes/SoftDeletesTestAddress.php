<?php

namespace Illuminate\Tests\App\Models\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftDeletesTestAddress extends Model
{
    use SoftDeletes;

    protected $table = 'addresses';
    protected $guarded = [];
}
