<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PivotSerializationTestTag extends Model
{
    public $table = 'tags';

    public function projects()
    {
        return $this->morphedByMany(PivotSerializationTestProject::class, 'taggable', 'taggables', 'tag_id', 'taggable_id')
            ->using(PivotSerializationTestTagAttachment::class);
    }
}
