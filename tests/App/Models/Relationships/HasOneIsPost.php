<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasOneIsPost extends Model
{
    protected $table = 'posts';

    public function attachment()
    {
        return $this->hasOne(Attachment::class, 'post_id');
    }
}
