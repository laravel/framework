<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class AsPivotPostPivot extends Model
{
    use AsPivot;

    protected $table = 'post_posts';
}
