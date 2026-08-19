<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Post extends Model
{
    public function attachment(): HasOne
    {
        return $this->hasOne(FakeRelationship::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(FakeRelationship::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FakeRelationship::class);
    }

    public function owner(): MorphOne
    {
        return $this->morphOne(FakeRelationship::class, 'property');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(FakeRelationship::class, 'actionable');
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(FakeRelationship::class);
    }

    public function lovers(): HasManyThrough
    {
        return $this->hasManyThrough(FakeRelationship::class, FakeRelationship::class);
    }

    public function contract(): HasOneThrough
    {
        return $this->hasOneThrough(FakeRelationship::class, FakeRelationship::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(FakeRelationship::class, 'taggable');
    }

    public function postable(): MorphTo
    {
        return $this->morphTo();
    }
}
