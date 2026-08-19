<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MorphManyPost extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = [];
    protected $withCount = ['comments'];

    public function comments()
    {
        return $this->morphMany(MorphManyComment::class, 'commentable');
    }

    public function latestComment(): MorphOne
    {
        return $this->comments()->one()->latestOfMany();
    }

    public function oldestComment(): MorphOne
    {
        return $this->comments()->one()->oldestOfMany();
    }
}
