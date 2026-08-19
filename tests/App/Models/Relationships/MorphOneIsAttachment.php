<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class MorphOneIsAttachment extends Model
{
    protected $table = 'attachments';

    public $timestamps = false;
}
