<?php

namespace Illuminate\Tests\App\Models\Prunable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrunableSoftDeleteTestModel extends Model
{
    use Prunable, SoftDeletes;

    public function prunable()
    {
        return $this->where('id', '<=', 3000);
    }
}
