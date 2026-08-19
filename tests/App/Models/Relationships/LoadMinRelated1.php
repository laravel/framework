<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LoadMinRelated1 extends Model
{
    protected $table = 'related1s';

    public $timestamps = false;

    protected $fillable = ['base_model_id', 'number'];

    public function parent()
    {
        return $this->belongsTo(LoadMinBaseModel::class);
    }
}
