<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticle extends Model
{
    protected $table = 'articles';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'title'];

    public function users()
    {
        return $this
            ->belongsToMany(DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticle::class, 'article_user', 'article_id', 'user_id')
            ->using(DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticleUser::class)
            ->withTimestamps();
    }
}
