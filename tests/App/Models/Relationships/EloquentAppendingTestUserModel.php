<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EloquentAppendingTestUserModel extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['appended_field'];

    public function getAppendedFieldAttribute()
    {
        return 'hello';
    }

    public function getOtherAppendedFieldAttribute()
    {
        return 'bye';
    }

    public function articles()
    {
        return $this->hasMany(EloquentTestArticleModel::class, 'user_id');
    }
}
