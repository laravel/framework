<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PivotSerializationTestProject extends Model
{
    public $table = 'projects';

    public function collaborators()
    {
        return $this->belongsToMany(
            PivotSerializationTestUser::class, 'project_users', 'project_id', 'user_id'
        )->using(PivotSerializationTestCollaborator::class);
    }

    public function tags()
    {
        return $this->morphToMany(PivotSerializationTestTag::class, 'taggable', 'taggables', 'taggable_id', 'tag_id')
            ->using(PivotSerializationTestTagAttachment::class);
    }
}
