<?php

namespace Illuminate\Tests\Integration\Database\Fixtures;

use Illuminate\Database\Eloquent\Model;

class PostStringyKey extends Model
{
    public $table = 'my_posts';

    public $primaryKey = 'my_id';
}
