<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];
}
