<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class DatabaseEloquentBelongsToManyExpressionTestTestPost extends Model
{
    protected $table = 'posts';
    protected $fillable = ['id'];
    public $timestamps = false;

    public function tags(): MorphToMany
    {
        return  $this->morphToMany(
            DatabaseEloquentBelongsToManyExpressionTestTestTag::class,
            'taggable',
            'taggables',
            'taggable_id',
            'tag_id',
            'id',
            'id',
        );
    }
}
