<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;

class GlobalScopeTouchingComment extends Model
{
    public $table = 'comments';
    public $timestamps = true;
    protected $guarded = [];
    protected $touches = ['post'];

    public function post()
    {
        return $this->belongsTo(GlobalScopeTouchingPost::class, 'post_id');
    }
}
