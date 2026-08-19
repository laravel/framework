<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot as EloquentPivot;

class DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticleUser extends EloquentPivot
{
    protected $table = 'article_user';
    protected $fillable = ['article_id', 'user_id'];
    protected $touches = ['article'];

    public function article()
    {
        return $this->belongsTo(DatabaseEloquentBelongsToManySyncTouchesParentTestTestArticle::class, 'article_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(DatabaseEloquentBelongsToManySyncTouchesParentTestTestUser::class, 'user_id', 'id');
    }
}
