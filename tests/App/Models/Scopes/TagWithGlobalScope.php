<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;

class TagWithGlobalScope extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            $query->select('tags.id');
        });
    }
}
