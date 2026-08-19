<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ManyToManyWithAttributesPost extends Model
{
    protected $guarded = [];
    protected $table = 'with_attributes_posts';

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ManyToManyWithAttributesTag::class,
            'with_attributes_pivot',
            'tag_id',
            'post_id',
        );
    }

    public function metaTags(): BelongsToMany
    {
        return $this->tags()
            ->withAttributes('visible', true)
            ->withPivotValue('type', 'meta');
    }

    public function morphedTags(): MorphToMany
    {
        return $this
            ->morphToMany(
                ManyToManyWithAttributesTag::class,
                'taggable',
                'with_attributes_taggables',
                relatedPivotKey: 'tag_id'
            )
            ->withAttributes('visible', true)
            ->withPivotValue('type', 'meta');
    }
}
