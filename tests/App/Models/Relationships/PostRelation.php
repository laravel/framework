<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PostRelation extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function postSubRelations()
    {
        return $this->hasMany(PostSubRelation::class);
    }
}
