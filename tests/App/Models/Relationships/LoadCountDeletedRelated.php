<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoadCountDeletedRelated extends Model
{
    use SoftDeletes;

    protected $table = 'deleted_related';

    public $timestamps = false;

    protected $fillable = ['base_model_id'];

    public function parent()
    {
        return $this->belongsTo(LoadCountBaseModel::class);
    }
}
