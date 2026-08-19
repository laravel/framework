<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Casts\AsBinary;
use Illuminate\Database\Eloquent\Model;

class AsBinaryTestModel extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'uuid' => AsBinary::class.':uuid',
            'ulid' => AsBinary::class.':ulid',
            'no_format' => AsBinary::class,
            'invalid_format' => AsBinary::class.':invalid',
        ];
    }
}
