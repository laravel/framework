<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Tests\App\Factories\HasManyInversePostModelFactory;

class HasManyInversePostModel extends Model
{
    use HasFactory;

    protected $table = 'test_posts';
    protected $fillable = ['id', 'user_id'];

    protected static function newFactory()
    {
        return new HasManyInversePostModelFactory();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(HasManyInverseUserModel::class, 'user_id');
    }
}
