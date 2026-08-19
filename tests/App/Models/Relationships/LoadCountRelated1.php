<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LoadCountRelated1 extends Model
{
    protected $table = 'related1s';

    public $timestamps = false;

    protected $fillable = ['base_model_id'];

    public function parent()
    {
        return $this->belongsTo(LoadCountBaseModel::class);
    }
}
