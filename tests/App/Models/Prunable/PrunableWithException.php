<?php

namespace Illuminate\Tests\App\Models\Prunable;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class PrunableWithException extends Model
{
    use Prunable;

    public function prunable()
    {
        return $this->where('id', '<=', 1000);
    }

    public function prune()
    {
        if ($this->id === 500) {
            throw new Exception('foo bar');
        }
    }
}
