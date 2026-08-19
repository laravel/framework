<?php

namespace Illuminate\Tests\App\Models\Keys;

use Illuminate\Database\Eloquent\Model;

class PostStringyKey extends Model
{
    public $table = 'my_posts';

    public $primaryKey = 'my_id';
}
