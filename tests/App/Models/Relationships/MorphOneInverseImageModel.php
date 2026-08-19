<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Tests\App\Factories\MorphOneInverseImageModelFactory;

class MorphOneInverseImageModel extends Model
{
    use HasFactory;

    protected $table = 'test_images';
    protected $fillable = ['id', 'imageable_type', 'imageable_id'];

    protected static function newFactory()
    {
        return new MorphOneInverseImageModelFactory();
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo('imageable');
    }
}
