<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ManyToManyPendingAttributesPost extends Model
{
    protected $guarded = [];
    protected $table = 'pending_attributes_posts';

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ManyToManyPendingAttributesTag::class,
            'pending_attributes_pivot',
            'tag_id',
            'post_id',
        );
    }

    public function metaTags(): BelongsToMany
    {
        return $this->tags()
            ->withAttributes('visible', true, asConditions: false)
            ->withPivotValue('type', 'meta');
    }

    public function morphedTags(): MorphToMany
    {
        return $this
            ->morphToMany(
                ManyToManyPendingAttributesTag::class,
                'taggable',
                'pending_attributes_taggables',
                relatedPivotKey: 'tag_id'
            )
            ->withAttributes('visible', true, asConditions: false)
            ->withPivotValue('type', 'meta');
    }
}
