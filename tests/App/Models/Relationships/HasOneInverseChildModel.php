<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Tests\App\Factories\HasOneInverseChildModelFactory;

class HasOneInverseChildModel extends Model
{
    use HasFactory;

    protected $table = 'test_child';
    protected $fillable = ['id', 'parent_id'];

    protected static function newFactory()
    {
        return new HasOneInverseChildModelFactory();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(HasOneInverseParentModel::class, 'parent_id');
    }
}
