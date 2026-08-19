<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LoadSumRelated2 extends Model
{
    protected $table = 'related2s';

    public $timestamps = false;

    protected $fillable = ['base_model_id', 'number'];

    public function parent()
    {
        return $this->belongsTo(LoadSumBaseModel::class);
    }
}
