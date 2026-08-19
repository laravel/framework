<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class DatabaseEloquentBelongsToManySyncTouchesParentTestTestUser extends Model
{
    protected $table = 'users';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'email'];

    public function articles()
    {
        return $this
            ->belongsToMany(DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticle::class, 'article_user', 'user_id', 'article_id')
            ->using(DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticleUser::class)
            ->withTimestamps();
    }
}
