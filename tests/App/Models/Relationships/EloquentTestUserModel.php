<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EloquentTestUserModel extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;

    public function articles()
    {
        return $this->hasMany(EloquentTestArticleModel::class, 'user_id');
    }
}
