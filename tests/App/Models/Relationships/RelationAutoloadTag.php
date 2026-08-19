<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelationAutoloadTag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    public $timestamps = false;

    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($model) {
            if ($model->post->shouldApplyStatus()) {
                $model->status = 'Todo';
            }
        });
    }

    protected static function newFactory()
    {
        return RelationAutoloadTagFactory::new();
    }

    public function post()
    {
        return $this->belongsTo(RelationAutoloadPost::class);
    }
}
