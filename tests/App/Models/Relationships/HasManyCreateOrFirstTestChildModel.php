<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasManyCreateOrFirstTestChildModel extends Model
{
    protected $table = 'child_table';
    protected $guarded = [];
}
