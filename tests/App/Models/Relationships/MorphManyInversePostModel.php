<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Tests\App\Factories\MorphManyInversePostModelFactory;

class MorphManyInversePostModel extends Model
{
    use HasFactory;

    protected $table = 'test_posts';
    protected $fillable = ['id'];

    protected static function newFactory()
    {
        return new MorphManyInversePostModelFactory();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(MorphManyInverseCommentModel::class, 'commentable')->inverse('commentable');
    }

    public function guessedComments(): MorphMany
    {
        return $this->morphMany(MorphManyInverseCommentModel::class, 'commentable')->inverse();
    }

    public function lastComment(): MorphOne
    {
        return $this->morphOne(MorphManyInverseCommentModel::class, 'commentable')->latestOfMany()->inverse('commentable');
    }

    public function guessedLastComment(): MorphOne
    {
        return $this->morphOne(MorphManyInverseCommentModel::class, 'commentable')->latestOfMany()->inverse();
    }

    public function firstComment(): MorphOne
    {
        return $this->comments()->one();
    }
}
