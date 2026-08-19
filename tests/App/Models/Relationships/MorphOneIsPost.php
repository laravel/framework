<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphOneIsPost extends Model
{
    protected $table = 'posts';

    public function attachment()
    {
        return $this->morphOne(MorphOneIsAttachment::class, 'attachable');
    }
}
