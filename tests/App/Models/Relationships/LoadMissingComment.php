<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LoadMissingComment extends Model
{
    protected $table = 'comments';

    public $timestamps = false;

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(self::class);
    }

    public function revisions()
    {
        return $this->hasMany(Revision::class, 'comment_id');
    }
}
