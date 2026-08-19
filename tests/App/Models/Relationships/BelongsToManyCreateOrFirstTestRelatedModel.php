<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class BelongsToManyCreateOrFirstTestRelatedModel extends Model
{
    protected $table = 'related_table';
    protected $guarded = [];
}
