<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Tests\App\Factories\MorphManyInverseCommentModelFactory;

class MorphManyInverseCommentModel extends Model
{
    use HasFactory;

    protected $table = 'test_comments';
    protected $fillable = ['id', 'commentable_type', 'commentable_id'];

    protected static function newFactory()
    {
        return new MorphManyInverseCommentModelFactory();
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo('commentable');
    }
}
