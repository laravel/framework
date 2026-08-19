<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ManyToManyPendingAttributesTag extends Model
{
    protected $guarded = [];
    protected $table = 'pending_attributes_tags';

    public function morphedPosts(): MorphToMany
    {
        return $this
            ->morphedByMany(
                ManyToManyPendingAttributesPost::class,
                'taggable',
                'pending_attributes_taggables',
                'tag_id',
            )
            ->withAttributes('title', 'Title!', asConditions: false)
            ->withPivotValue('type', 'meta');
    }
}
