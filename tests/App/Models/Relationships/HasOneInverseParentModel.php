<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Tests\App\Factories\HasOneInverseParentModelFactory;

class HasOneInverseParentModel extends Model
{
    use HasFactory;

    protected $table = 'test_parent';

    protected $fillable = ['id'];

    protected static function newFactory()
    {
        return new HasOneInverseParentModelFactory();
    }

    public function child(): HasOne
    {
        return $this->hasOne(HasOneInverseChildModel::class, 'parent_id')->inverse('parent');
    }
}
