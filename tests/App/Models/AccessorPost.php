<?php

namespace Illuminate\Tests\App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessorPost extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * Return whether the post is published.
     *
     * @return bool
     */
    public function getIsPublishedAttribute()
    {
        return true;
    }
}
