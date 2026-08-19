<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LoadCountRelated2 extends Model
{
    protected $table = 'related2s';

    public $timestamps = false;

    protected $fillable = ['base_model_id'];

    public function parent()
    {
        return $this->belongsTo(LoadCountBaseModel::class);
    }
}
