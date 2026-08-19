<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PostSubRelation extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function postSubSubRelations()
    {
        return $this->hasMany(PostSubSubRelation::class);
    }
}
