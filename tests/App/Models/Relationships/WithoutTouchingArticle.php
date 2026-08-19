<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WithoutTouchingArticle extends Model
{
    protected $table = 'articles';
    protected $fillable = ['id', 'title'];
    protected $touches = ['user'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(WithoutTouchingUser::class, 'article_user', 'article_id', 'user_id');
    }
}
