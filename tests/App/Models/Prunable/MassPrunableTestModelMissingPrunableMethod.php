<?php

namespace Illuminate\Tests\App\Models\Prunable;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class MassPrunableTestModelMissingPrunableMethod extends Model
{
    use MassPrunable;
}
