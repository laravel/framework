<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ManyToManyWithAttributesTag extends Model
{
    protected $guarded = [];
    protected $table = 'with_attributes_tags';

    public function morphedPosts(): MorphToMany
    {
        return $this
            ->morphedByMany(
                ManyToManyWithAttributesPost::class,
                'taggable',
                'with_attributes_taggables',
                'tag_id',
            )
            ->withAttributes('title', 'Title!')
            ->withPivotValue('type', 'meta');
    }
}
