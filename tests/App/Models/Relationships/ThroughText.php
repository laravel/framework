<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class ThroughText extends Model
{
    protected $table = 'texts';

    public $timestamps = false;

    protected $guarded = [];

    public function post()
    {
        return $this->belongsTo(ThroughPost::class);
    }
}
