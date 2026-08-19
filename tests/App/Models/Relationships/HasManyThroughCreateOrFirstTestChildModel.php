<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasManyThroughCreateOrFirstTestChildModel extends Model
{
    protected $table = 'child';
    protected $guarded = [];
}
