<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EloquentTestArticleModel extends Model
{
    protected $table = 'articles';
    protected $guarded = [];
    public $timestamps = false;

    public function comments()
    {
        return $this->hasMany(EloquentTestCommentModel::class, 'article_id');
    }
}
