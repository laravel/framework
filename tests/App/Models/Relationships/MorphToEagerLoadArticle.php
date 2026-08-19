<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Enums\ArticleSlug;

class MorphToEagerLoadArticle extends Model
{
    protected $table = 'articles';

    public $timestamps = false;

    protected $primaryKey = 'slug';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = ['slug' => ArticleSlug::class];

    protected $fillable = ['slug'];
}
