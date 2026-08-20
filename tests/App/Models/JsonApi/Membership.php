<?php

namespace Illuminate\Tests\App\Models\JsonApi;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Membership extends Pivot
{
    protected $table = 'team_user';
}
